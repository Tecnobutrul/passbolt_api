#!/usr/bin/env bash

set -euo pipefail

su -c "./bin/cake migrations migrate -p Passbolt/MultiTenantAdmin" -s /bin/bash www-data
su -c "./bin/cake migrations migrate --plugin EmailQueue --connection emailQueue" -s /bin/bash www-data

email_cron_job() {
  cron_task='/etc/cron.d/passbolt_email'
  if [ ! -f "$cron_task" ]; then
    echo "* * * * * su -c \"/var/www/passbolt/bin/cake EmailQueue.sender\" -s /bin/bash www-data >> /var/log/cron.log 2>&1" >> $cron_task
    crontab /etc/cron.d/passbolt_email
  fi
}

email_cron_job

/usr/bin/supervisord -n
