#!/bin/bash

set -o errexit
set -o nounset
set -o pipefail

echo "Install Tuleap - ${RUN_MODE:-interactive}"

/install.sh

echo "Start Tuleap system"

systemctl start tuleap

if [ "${RUN_MODE:-interactive}" == "interactive" ]; then
    exit 0
fi

echo "Entering CI mode"

set -x

count=0
code=$(curl --insecure --silent --output /dev/null --write-out "%{http_code}"  https://127.0.0.1 || true)
while [ $code -ne 200 ] && [ $count -ne 10 ]; do
  echo "$(date) Tuleap not ready ($code) attempt #$count"
  sleep $((count+1))
  code=$(curl --insecure --silent --output /dev/null --write-out "%{http_code}" https://127.0.0.1 || true)
  count=$((count+1))
done

install -d -m 755 /output
curl --verbose --insecure --output /output/api-version.json https://127.0.0.1/api/version
