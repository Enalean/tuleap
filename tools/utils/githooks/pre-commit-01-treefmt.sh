#!/usr/bin/env bash

exec treefmt --fail-on-change --quiet -- $(git diff --cached --name-only --diff-filter=ACMRTUXB)
