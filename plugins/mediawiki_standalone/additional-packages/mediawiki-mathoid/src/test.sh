#!/usr/bin/env sh

set -euxo pipefail

cd "$(dirname $0)"

./../dist/mathoid-cli --config ./config.yaml < ./sample-cli-input.json

if [ "$(./../dist/mathoid-cli --config ./config.yaml < ./sample-cli-input.json)" == "$(cat ./sample-cli-output.json)" ]
then
  echo "Got the expected output"
  exit 0
fi

echo "The output does not match the expected content"
exit 1
