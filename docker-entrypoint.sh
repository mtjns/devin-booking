#!/bin/sh
set -e

# Fix storage permissions for development environments with mounted volumes
chmod 777 storage storage/logs storage/framework storage/framework/cache storage/framework/views storage/framework/sessions bootstrap/cache 2>/dev/null || true

# Clear stale provider cache that may reference removed packages
rm -f bootstrap/cache/packages.php

# Run supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
