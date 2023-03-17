#! /usr/bin/env bash

source /var/lib/tuleap/tuleap-realtime-key
exec sudo -u tuleaprt NODE_ENV=production PRIVATE_KEY="$PRIVATE_KEY" /usr/lib/tuleap-realtime/tuleap-realtime
