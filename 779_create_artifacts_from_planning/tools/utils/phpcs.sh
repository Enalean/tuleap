#!/bin/sh

set -e

#
# Set environment

# PHP
if [ -z "$PHP" ]; then
    PHP=$(which php)
fi
if [ ! -x "$PHP" ]; then
    echo "*** ERROR: php executable not found in path nor in PHP variable"
    exit 1
fi

# PHPCS
if [ -z "$PHPCS" ]; then
    PHPCS=$(which phpcs)
fi
if [ ! -x "$PHPCS" ]; then
    echo "*** ERROR: phpcs executable not found in path nor in PHPCS variable"
    exit 1
fi

# Codendi sources
if [ -z "$CODENDI_LOCAL_INC" ]; then 
    CODENDI_LOCAL_INC=/etc/codendi/conf/local.inc
fi
if [ ! -f "$CODENDI_LOCAL_INC" ]; then
    echo "*** ERROR: unable to find local.inc, please set CODENDI_LOCAL_INC variable"
    exit 1
fi

CODENDI_DIR=`/bin/grep '^\$codendi_dir' $CODENDI_LOCAL_INC | /bin/sed -e 's/\$codendi_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`

$PHP -d memory_limit=256M $PHPCS --standard="$CODENDI_DIR/tools/utils/phpcs/Codendi" -n $@
