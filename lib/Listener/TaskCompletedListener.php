<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TaskTracker\Listener;

use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VTodo;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;

// see
// - https://github.com/nextcloud/spreed/blob/main/lib/Listener/CalDavEventListener.php
// - https://help.nextcloud.com/t/simple-task-complete-listener-log-ping-other-server/244358

class TaskCompletedListener implements IEventListener {
    public function __construct(
        private LoggerInterface $logger,
        private IClientService $clientService,
        private IAppConfig $appConfig,
    ) {}

    public function handle(Event $event): void {
        if (!$event instanceof CalendarObjectUpdatedEvent) {
            return;
        }

        $principaluri = $event->getCalendarData()['principaluri'] ?? null;
        if (!$principaluri) {
			$this->logger->warning('No principal uri. Skipping.');
			return;
		}

        if ($principaluri !== 'principals/users/adam') {
			$this->logger->info('Does not belong to Adam. Skipping.');
			return;
		}

        $calendarData = $event->getObjectData()['calendardata'] ?? null;

        if (!$calendarData) {
			$this->logger->warning('No calendar data found');
			return;
		}

        try {
            $vobj = Reader::read($calendarData);
        } catch (ParseException $e) { /** Undocumented in sabre code */
			$this->logger->warning($e->getMessage());
			return;
		}

        // tasks only (not VEVENT, VJOURNAL, etc.)
        if (isset($vobj->VTODO)) {
            $todo = $vobj->VTODO;

            if (isset($todo->STATUS) && (string)$todo->STATUS === 'COMPLETED') {
                $this->pingMultiTracker($todo);
            }
        }
    }

    private function pingMultiTracker(VTodo $todo): void {
        $title = isset($todo->SUMMARY) ? (string)$todo->SUMMARY : 'Untitled Task';
        $detail = isset($todo->DESCRIPTION) ? (string)$todo->DESCRIPTION : '';
        $categories = isset($todo->CATEGORIES) ? (string)$todo->CATEGORIES : '';
        $location = isset($todo->LOCATION) ? (string)$todo->LOCATION : '';
        $url = isset($todo->URL) ? (string)$todo->URL : '';

        try {
            $apiKey = $this->appConfig->getAppValueString(key: 'apiKey');
            $endpoint = $this->appConfig->getAppValueString(key: 'endpoint');
            $headers = ['X-API-Key' => $apiKey];
            $client = $this->clientService->newClient();
            $uid = (string)$todo->UID;
            $client->post("$endpoint/$uid", [
                'headers' => $headers,
                'json' => [
                    'title'    => $title,
                    'detail'   => $detail,
                    'src'      => 'task-tracker',
                    'tags'     => explode(',', $categories),
                    'location' => $location,
                    'url'      => $url,
                ],
                'timeout' => 2
            ]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
