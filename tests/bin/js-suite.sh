#!/bin/bash

# Set environment
set -e
SOURCE_ROOT_PATH=$(cd `dirname $0`; cd ../..; pwd)

# Run
if [ "$1" = "ci" ]; then
    mocha --reporter xunit $SOURCE_ROOT_PATH/tests/js/mocha/test > mocha.xml
    mocha-phantomjs --reporter xunit $SOURCE_ROOT_PATH/plugins/tests/www/mocha/index.html > mocha-phantomjs.xml
else 
    mocha $SOURCE_ROOT_PATH/tests/js/mocha/test
    mocha-phantomjs $SOURCE_ROOT_PATH/plugins/tests/www/mocha/index.html
fi
