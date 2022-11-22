#!/bin/bash

set -ex -o pipefail

/install.sh

systemctl start tuleap
