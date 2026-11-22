# Nextcloud task-tracker

Logs completed tasks to custom/personal task tracker server.

## update

```bash
# on server
cd /opt/nextcloud-more-apps/task_tracker
git pull
```

If Nextcloud fails to pick up the code changes, try

```bash
occ app:disable task_tracker
occ app:enable task_tracker
```

Or `docker compose restart app`.

## one-time setup

```bash
occ config:app:set --sensitive --value=REDACTED task_tracker apiKey
occ config:app:set --sensitive --value=REDACTED task_tracker endpoint
```

Originally based on [Arnout Engelen's calendar_audit code](https://codeberg.org/raboof/nextcloud_calendar_audit).
Got some terrible help from Gemini and ChatGPT and some [excellent help from the Nextcloud community](https://help.nextcloud.com/t/simple-task-complete-listener-log-ping-other-server/244358/).

## Copyright and license

Copyright ©2026 [Adam Monsen](https://adammonsen.com)

License: AGPL v3 or later (see COPYING).
