#!/usr/bin/env bash

set -euo pipefail

su -c "./bin/cake migrations migrate -p Passbolt/MultiTenantAdmin" -s /bin/bash www-data
su -c "./bin/cake migrations migrate --plugin EmailQueue --connection emailQueue" -s /bin/bash www-data

/usr/bin/supervisord -n
