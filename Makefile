# How to:
# Run the rest tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_api_test
# Run the phpunit tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_phpunit

TULEAP_INCLUDE_PATH=$(CURDIR)/src/www/include:$(CURDIR)/src:/usr/share/codendi/src/www/include:/usr/share/codendi/src
PHP_INCLUDE_PATH=/usr/share/php:/usr/share/pear:$(TULEAP_INCLUDE_PATH):/usr/share/jpgraph:.
PHP=php -q -d date.timezone=Europe/Paris -d include_path=$(PHP_INCLUDE_PATH) -d memory_limit=256M -d display_errors=On

ifeq ($(BUILD_ENV),ci)
OUTPUT_DIR=$(WORKSPACE)
SIMPLETEST_OPTIONS=-x
REST_TESTS_OPTIONS=--log-junit $(OUTPUT_DIR)/rest_tests.xml
PHPUNIT_TESTS_OPTIONS=--log-junit $(OUTPUT_DIR)/phpunit_tests.xml --coverage-html $(OUTPUT_DIR)/phpunit_coverage --coverage-clover $(OUTPUT_DIR)/phpunit_coverage/coverage.xml
PHPUNIT_OPTIONS=
TULEAP_LOCAL_INC=$(WORKSPACE)/etc/integration_tests.inc
COMPOSER=/usr/local/bin/composer.phar
else
SIMPLETEST_OPTIONS=
REST_TESTS_OPTIONS=
PHPUNIT_TESTS_OPTIONS=
PHPUNIT_OPTIONS=--color
TULEAP_LOCAL_INC=/etc/codendi/conf/integration_tests.inc
COMPOSER=$(CURDIR)/composer.phar
endif

# Export
export TULEAP_LOCAL_INC

PHPUNIT=$(PHP) vendor/phpunit/phpunit/phpunit.php $(PHPUNIT_OPTIONS)
SIMPLETEST=$(PHP) tests/bin/simpletest $(SIMPLETEST_OPTIONS)

AUTOLOAD_EXCLUDES=mediawiki|tests|template

default:
	@echo "possible targets: 'doc' 'test' 'autoload' 'less' 'less-dev' 'api_test' 'api_test_group'"

doc:
	$(MAKE) -C documentation all

autoload:
	@echo "Generate core"
	@(cd src/common; phpab -q --compat -o autoload.php --exclude "./wiki/phpwiki/*" .)
	@echo "Generate tests"
	@(cd tests/lib; phpab  -q --compat -o autoload.php .)
	@for path in `ls plugins | egrep -v "$(AUTOLOAD_EXCLUDES)"`; do \
	     echo "Generate plugin $$path"; \
	     (cd "plugins/$$path/include"; phpab -q --compat -o autoload.php .) \
        done;

autoload-dev:
	@tools/utils/autoload.sh

less:
	@tools/utils/less.sh less `pwd`

less-dev:
	@tools/utils/less.sh watch `pwd`

composer_update:
	cp tests/rest/bin/composer.json .
	php $(COMPOSER) install

local_composer_install:
	curl -k -sS https://getcomposer.org/installer | php

api_test_setup: local_composer_install composer_update
	cp tests/rest/bin/integration_tests.inc.dist /etc/codendi/conf/integration_tests.inc
	cp tests/rest/bin/dbtest.inc.dist /etc/codendi/conf/dbtest.inc

api_test_bootstrap:
	php tests/lib/rest/init_db.php
	$(PHP) tests/lib/rest/init_data.php

api_test: composer_update api_test_bootstrap
	$(PHPUNIT) $(REST_TESTS_OPTIONS) tests/rest
	@if [ -e plugins/*/tests/rest ]; then \
		$(PHPUNIT) $(REST_TESTS_OPTIONS) plugins/*/tests/rest; \
	fi

ci_api_test_setup: composer_update
	mkdir -p $(WORKSPACE)/etc
	cat tests/rest/bin/integration_tests.inc.dist | perl -pe "s%/usr/share/codendi%$(CURDIR)%" > $(TULEAP_LOCAL_INC)
	cp tests/rest/bin/dbtest.inc.dist $(WORKSPACE)/etc/dbtest.inc
	mkdir -p /tmp/run
	php tests/bin/generate-phpunit-testsuite.php /tmp/run $(OUTPUT_DIR)

ci_api_test: ci_api_test_setup api_test

docker_api_all:
	$(PHP) /tmp/run/vendor/phpunit/phpunit/phpunit.php --configuration /tmp/run/suite.xml

docker_api_partial:
	$(PHP) /tmp/run/vendor/phpunit/phpunit/phpunit.php $(REST_TESTS_OPTIONS)

phpunit:
	$(PHPUNIT) $(PHPUNIT_TESTS_OPTIONS) --bootstrap tests/phpunit_boostrap.php plugins/proftpd/phpunit

ci_phpunit: composer_update phpunit

simpletest:
	$(SIMPLETEST) $(SIMPLETEST_OPTIONS) tests/simpletest plugins tests/integration

ci_simpletest: simpletest

test: simpletest phpunit api_test
