#! /usr/bin/env bash

export "$(cat /var/lib/tuleap/tuleap-realtime-key)"
exec sudo --preserve-env=PRIVATE_KEY -u tuleaprt NODE_ENV=production /usr/lib/tuleap-realtime/tuleap-realtime
