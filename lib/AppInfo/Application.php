<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TaskTracker\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCA\TaskTracker\Listener\TaskCompletedListener;

class Application extends App implements IBootstrap {
    public const APP_ID = 'task_tracker';

    public function __construct(array $params = []) {
        parent::__construct(self::APP_ID, $params);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(
            CalendarObjectUpdatedEvent::class, 
            TaskCompletedListener::class
        );
    }

    public function boot(IBootContext $context): void {
        // Nothing needed at boot time
    }
}
