AUTOLOAD_EXCLUDES=mediawiki|tests|template

default:
	@echo "possible targets: 'doc' 'test' 'autoload' 'less' 'less-dev' 'api_test' 'api_test_group'"

doc:
	$(MAKE) -C documentation all

test:
	@php tests/bin/simpletest tests/simpletest plugins

autoload:
	@echo "Generate core"
	@(cd src/common; phpab -q --compat -o autoload.php --exclude "./wiki/phpwiki/*" .)
	@echo "Generate tests"
	@(cd tests/lib; phpab  -q --compat -o autoload.php .)
	@for path in `ls plugins | egrep -v "$(AUTOLOAD_EXCLUDES)"`; do \
	     echo "Generate plugin $$path"; \
	     (cd "plugins/$$path/include"; phpab -q --compat -o autoload.php .) \
        done;

less:
	@tools/utils/less.sh less `pwd`

less-dev:
	@tools/utils/less.sh watch `pwd`

api_test_setup:
	cp tests/rest/bin/composer.json .
	curl -k -sS https://getcomposer.org/installer | php
	php composer.phar install
	cp tests/rest/bin/integration_tests.inc.dist /etc/codendi/conf/integration_tests.inc
	cp tests/rest/bin/dbtest.inc.dist /etc/codendi/conf/dbtest.inc

api_test_bootstrap:
	php tests/lib/rest/init_db.php
	TULEAP_LOCAL_INC=/etc/codendi/conf/integration_tests.inc src/utils/php-launcher.sh tests/lib/rest/init_data.php

api_test: api_test_bootstrap
	src/utils/php-launcher.sh vendor/phpunit/phpunit/phpunit.php tests/rest
