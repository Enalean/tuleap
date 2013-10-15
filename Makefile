AUTOLOADED_PATH=src/common/PFO plugins/agiledashboard/include plugins/cardwall/include plugins/fulltextsearch/include plugins/tracker/include plugins/git/include src/common/project src/common/user src/common/Token src/common/REST plugins/boomerang/include plugins/openid/include tests/lib

default:
	@echo "possible targets: 'doc' 'test' 'autoload' 'api_test' 'api_test_group'"

doc:
	$(MAKE) -C documentation all

test:
	@php tests/bin/simpletest tests/simpletest plugins

autoload:
	@echo "Generate core"
	@(cd src/common; phpab  --compat -o autoload.php --exclude "./wiki/phpwiki/*" .)
	@for path in $(AUTOLOADED_PATH); do \
	     echo "Generate $$path"; \
	     (cd "$$path"; phpab --compat -o autoload.php .) \
        done;

api_test_setup:
	cp tests/rest/bin/composer.json .
	curl -sS https://getcomposer.org/installer | php
	php composer.phar install
	cp tests/rest/bin/integration_tests.inc.dist /etc/codendi/conf/integration_tests.inc
	cp tests/rest/bin/dbtest.inc.dist /etc/codendi/conf/dbtest.inc

api_test:
	src/utils/php-launcher.sh vendor/phpunit/phpunit/phpunit.php --bootstrap tests/lib/rest/bootstrap.php tests/rest

api_test_group:
	src/utils/php-launcher.sh vendor/phpunit/phpunit/phpunit.php --group $g tests/rest