# How to:
# Run the rest tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_api_test
# Run the phpunit tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_phpunit

TULEAP_INCLUDE_PATH=$(CURDIR)/src/www/include:$(CURDIR)/src:/usr/share/codendi/src/www/include:/usr/share/codendi/src
PHP_INCLUDE_PATH=/usr/share/php:/usr/share/pear:$(TULEAP_INCLUDE_PATH):/usr/share/jpgraph:.
PHP=php -q -d date.timezone=Europe/Paris -d include_path=$(PHP_INCLUDE_PATH) -d memory_limit=256M -d display_errors=On

DOCKER_REST_TESTS_IMAGE=enalean/tuleap-test-rest
DOCKER_REST_TESTS_IMGINIT=$(DOCKER_REST_TESTS_IMAGE)-init

ifeq ($(BUILD_ENV),ci)
OUTPUT_DIR=$(WORKSPACE)
SIMPLETEST_OPTIONS=-x
REST_TESTS_OPTIONS=--log-junit $(OUTPUT_DIR)/rest_tests.xml
SOAP_TESTS_OPTIONS=--log-junit $(OUTPUT_DIR)/soap_tests.xml
PHPUNIT_TESTS_OPTIONS=--log-junit $(OUTPUT_DIR)/phpunit_tests.xml --coverage-html $(OUTPUT_DIR)/phpunit_coverage --coverage-clover $(OUTPUT_DIR)/phpunit_coverage/coverage.xml
PHPUNIT_OPTIONS=
TULEAP_LOCAL_INC=$(WORKSPACE)/etc/integration_tests.inc
COMPOSER=/usr/local/bin/composer.phar
else
SIMPLETEST_OPTIONS=
REST_TESTS_OPTIONS=
SOAP_TESTS_OPTIONS=
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

autoload-with-userid:
	@echo "Generate core"
	@(cd src/common; phpab -q --compat -o autoload.php --exclude "./wiki/phpwiki/*" .;chown $(USER_ID):$(USER_ID) autoload.php)
	@echo "Generate tests"
	@(cd tests/lib; phpab  -q --compat -o autoload.php .;chown $(USER_ID):$(USER_ID) autoload.php)
	@for path in `ls plugins | egrep -v "$(AUTOLOAD_EXCLUDES)"`; do \
		echo "Generate plugin $$path"; \
		(cd "plugins/$$path/include"; phpab -q --compat -o autoload.php .;chown $(USER_ID):$(USER_ID) autoload.php) \
		done;

autoload-docker:
	@docker run --rm=true -v $(CURDIR):/tuleap enalean/tuleap-dev-swissarmyknife --user-id `id -u` --autoload

autoload-dev:
	@tools/utils/autoload.sh

less:
	@tools/utils/less.sh less `pwd`

less-dev:
	@tools/utils/less.sh watch `pwd`

less-docker:
	@docker run --rm=true -v $(CURDIR):/tuleap enalean/tuleap-dev-swissarmyknife --user-id `id -u` --less

composer_update:
	cp tests/rest/bin/composer.json .
	php $(COMPOSER) install

soap_composer_update:
	cp tests/soap/bin/composer.json .
	php $(COMPOSER) install

local_composer_install:
	curl -k -sS https://getcomposer.org/installer | php

api_test_setup: local_composer_install composer_update
	cp tests/rest/bin/integration_tests.inc.dist /etc/codendi/conf/integration_tests.inc
	cp tests/rest/bin/dbtest.inc.dist /etc/codendi/conf/dbtest.inc

api_test_bootstrap:
	php tests/lib/rest/init_db.php
	$(PHP) tests/lib/rest/init_data.php

soap_test_bootstrap:
	php tests/lib/soap/init_db.php
	$(PHP) tests/lib/soap/init_data.php

soap_test: composer_update soap_test_bootstrap
	$(PHPUNIT) $(SOAP_TESTS_OPTIONS) tests/soap

api_test: composer_update api_test_bootstrap
	$(PHPUNIT) $(REST_TESTS_OPTIONS) tests/rest
	@for restpath in `\ls -d plugins/*/tests/rest`; do \
		$(PHPUNIT) $(REST_TESTS_OPTIONS) "$$restpath"; \
	done;

ci_api_test_setup: composer_update
	mkdir -p $(WORKSPACE)/etc
	cat tests/rest/bin/integration_tests.inc.dist | perl -pe "s%/usr/share/codendi%$(CURDIR)%" > $(TULEAP_LOCAL_INC)
	cp tests/rest/bin/dbtest.inc.dist $(WORKSPACE)/etc/dbtest.inc
	mkdir -p /tmp/run
	php tests/bin/generate-phpunit-testsuite.php /tmp/run $(OUTPUT_DIR)

ci_soap_test_setup: soap_composer_update
	mkdir -p $(WORKSPACE)/etc
	cat tests/soap/bin/integration_tests.inc.dist | perl -pe "s%/usr/share/codendi%$(CURDIR)%" > $(TULEAP_LOCAL_INC)
	cp tests/soap/bin/dbtest.inc.dist $(WORKSPACE)/etc/dbtest.inc
	mkdir -p /tmp/run
	php tests/bin/generate-phpunit-testsuite-soap.php /tmp/run $(OUTPUT_DIR)

ci_api_test: ci_api_test_setup api_test

docker_api_all:
	$(PHP) /tmp/run/vendor/phpunit/phpunit/phpunit.php --configuration /tmp/run/suite.xml

docker_api_partial:
	$(PHP) /tmp/run/vendor/phpunit/phpunit/phpunit.php $(REST_TESTS_OPTIONS)

tests_php51:
	docker run --rm=true -v $(CURDIR):/tuleap enalean/tuleap-test-ut-c5-php51

tests_php53:
	docker run --rm=true -v $(CURDIR):/tuleap enalean/tuleap-test-ut-c6-php53

tests_phpunit:
	docker run -ti --rm=true -v $(CURDIR):/tuleap enalean/tuleap-test-phpunit-c6-php53

phpunit:
	$(PHPUNIT) $(PHPUNIT_TESTS_OPTIONS) --bootstrap tests/phpunit_boostrap.php plugins/proftpd/phpunit

ci_phpunit: composer_update phpunit

simpletest:
	$(SIMPLETEST) $(SIMPLETEST_OPTIONS) tests/simpletest plugins tests/integration

ci_simpletest: simpletest

test: simpletest phpunit api_test

rest_docker_snapshot:
	@docker run -ti --name=rest-init -v $(CURDIR):/tuleap $(DOCKER_REST_TESTS_IMAGE) --init
	@docker commit rest-init $(DOCKER_REST_TESTS_IMGINIT)
	@docker rm -f rest-init
	@echo "Image ready: $(DOCKER_REST_TESTS_IMGINIT)"
	@echo "You can use it like:"
	@echo "# docker run --rm=true -v $(CURDIR):/tuleap $(DOCKER_REST_TESTS_IMGINIT) --run"
	@echo "# docker run --rm=true -v $(CURDIR):/tuleap $(DOCKER_REST_TESTS_IMGINIT) --run tests/rest/ArtifactsTest.php"

rest_docker_clean:
	@docker rmi $(DOCKER_REST_TESTS_IMGINIT)

rest_docker_snap_run:
	@echo "Once inside the container, just run:"
	@echo "# ./run.sh --run tests/rest/UsersTest.php"
	@docker run -ti --rm=true -v $(CURDIR):/tuleap --entrypoint=/bin/bash $(DOCKER_REST_TESTS_IMGINIT) +x

#
# Start development enviromnent with Docker Compose
#

.env:
	@MYSQL_ROOT_PASSWORD=`cat /dev/urandom | tr -dc "a-zA-Z0-9" | fold -w 15 | head -1` && echo "MYSQL_ROOT_PASSWORD=$$MYSQL_ROOT_PASSWORD" > .env
	@LDAP_ROOT_PASSWORD=`cat /dev/urandom | tr -dc "a-zA-Z0-9" | fold -w 15 | head -1` && echo "LDAP_ROOT_PASSWORD=$$LDAP_ROOT_PASSWORD" >> .env
	@LDAP_MANAGER_PASSWORD=`cat /dev/urandom | tr -dc "a-zA-Z0-9" | fold -w 15 | head -1` && echo "LDAP_MANAGER_PASSWORD=$$LDAP_MANAGER_PASSWORD" >> .env
	@echo VIRTUAL_HOST=tuleap_web_1.tuleap-aio-dev.docker >> .env

dev-setup: .env
	@echo "Create all data containers"
	@docker inspect tuleap_ldap_data > /dev/null 2>&1 || docker run -t --name=tuleap_ldap_data -v /data busybox true
	@docker inspect tuleap_db_data > /dev/null 2>&1 || docker run -t --name=tuleap_db_data -v /var/lib/mysql busybox true
	@docker inspect tuleap_es_data > /dev/null 2>&1 || docker run -t --name=tuleap_es_data -v /data busybox true
	@docker inspect tuleap_data > /dev/null 2>&1 || docker run -t --name=tuleap_data -v /data busybox true
	@docker inspect tuleap_reverseproxy_data > /dev/null 2>&1 || docker run -t --name=tuleap_reverseproxy_data -v /reverseproxy_data busybox true

show-passwords:
	@docker run --rm --volumes-from tuleap_data busybox cat /data/root/.tuleap_passwd

start-dns:
	@docker run -d -v /var/run/docker.sock:/var/run/docker.sock --name dnsdock -p 172.17.42.1:53:53/udp tonistiigi/dnsdock

start-rp:
	@echo "Start reverse proxy"
	@docker-compose up --x-smart-recreate -d rp

start:
	@echo "Start Tuleap Web + LDAP + DB"
	@docker-compose up -d web
	@echo -n "Your instance will be soon available: http://"
	@grep VIRTUAL_HOST .env | cut -d= -f2
	@echo "You might want to type 'make show-passwords' to see site default passwords"

start-es:
	@docker-compose up -d es

start-all:
	echo "Start all containers (Web, LDAP, DB, Elasticsearch)"
	@docker-compose up -d
