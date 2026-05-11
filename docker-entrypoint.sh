#!/bin/sh
set -e

# Fix storage permissions for development environments with mounted volumes
chmod 777 storage storage/logs storage/framework storage/framework/cache storage/framework/views storage/framework/sessions bootstrap/cache 2>/dev/null || true

# Clear stale provider cache that may reference removed packages
rm -f bootstrap/cache/packages.php

# If command is passed (e.g., queue:work), run it directly
# Otherwise run supervisord
if [ $# -gt 0 ]; then
    exec "$@"
else
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
