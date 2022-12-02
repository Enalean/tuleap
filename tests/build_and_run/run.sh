#!/usr/bin/env bash

set -o errexit
set -o nounset
set -o pipefail

/install.sh

systemctl start tuleap

count=0
code=$(curl --insecure --silent --output /dev/null --write-out "%{http_code}"  https://127.0.0.1 || true)
while [ $code -ne 200 ] && [ $count -ne 10 ]; do
  echo "$(date) Tuleap not ready ($code) attempt #$count"
  sleep $((count+1))
  code=$(curl --insecure --silent --output /dev/null --write-out "%{http_code}" https://127.0.0.1 || true)
  count=$((count+1))
done

curl --silent --insecure --output /output/api-version.json https://127.0.0.1/api/version

timeout 20s journalctl --no-pager > /output/journalctl.log
