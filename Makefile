# How to:
# Run the rest tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_api_test
# Run the phpunit tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_phpunit
# Run docker as a priviledged user: make SUDO=sudo ... or make SUDO=pkexec ...

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

OS := $(shell uname)
ifeq ($(OS),Darwin)
DOCKER_COMPOSE_FILE=-f docker-compose.yml -f docker-compose-mac.yml
else
DOCKER_COMPOSE_FILE=-f docker-compose.yml
endif

SUDO=
DOCKER=$(SUDO) docker
DOCKER_COMPOSE=$(SUDO) docker-compose $(DOCKER_COMPOSE_FILE)

# Export
export TULEAP_LOCAL_INC

PHPUNIT=$(PHP) vendor/phpunit/phpunit/phpunit.php $(PHPUNIT_OPTIONS)
SIMPLETEST=$(PHP) tests/bin/simpletest $(SIMPLETEST_OPTIONS)

AUTOLOAD_EXCLUDES=^mediawiki|^tests|^template

.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo "(Other less used targets are available, open Makefile for details)"

#
# Utilities
#

doc: ## Build CLI documentation
	$(MAKE) -C documentation all

autoload:
	@echo "Generate core"
	@(cd src/common; phpab -q --compat -o autoload.php --exclude "./wiki/phpwiki/*" .)
	@for path in `ls src/www/themes | egrep -v "^Tuleap|^common|^FlamingParrot"`; do \
	     echo "Generate theme $$path"; \
	     (cd "src/www/themes/$$path/"; phpab -q --compat -o autoload.php .) \
        done;
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

autoload-docker: ## Generate autoload files
	@$(DOCKER) run --rm=true -v $(CURDIR):/tuleap -e USER=`id -u` -e GROUP=`id -g` enalean/tuleap-dev-swissarmyknife:2 --autoload

autoload-dev:
	@tools/utils/autoload.sh

## RNG generation

rnc2rng-docker: clean-rng ## Compile rnc file into rng
	@$(DOCKER) run --rm=true -v $(CURDIR):/tuleap -e USER=`id -u` -e GROUP=`id -g` enalean/tuleap-dev-swissarmyknife:2 --rnc2rng

rnc2rng: src/common/xml/resources/project/project.rng \
	 src/common/xml/resources/users.rng  \
	 plugins/svn/resources/svn.rng \
	 src/common/xml/resources/ugroups.rng \
	 plugins/tracker/www/resources/tracker.rng \
	 plugins/tracker/www/resources/trackers.rng \
	 plugins/tracker/www/resources/artifacts.rng \
	 plugins/agiledashboard/www/resources/xml_project_agiledashboard.rng \
	 plugins/cardwall/www/resources/xml_project_cardwall.rng

src/common/xml/resources/project/project.rng: src/common/xml/resources/project/project.rnc plugins/tracker/www/resources/tracker-definition.rnc src/common/xml/resources/ugroups-definition.rnc plugins/svn/resources/svn-definition.rnc src/common/xml/resources/frs-definition.rnc src/common/xml/resources/mediawiki-definition.rnc src/common/xml/resources/project-definition.rnc

plugins/svn/resources/svn.rng: plugins/svn/resources/svn.rnc plugins/svn/resources/svn-definition.rnc

src/common/xml/resources/ugroups.rng: src/common/xml/resources/ugroups.rnc src/common/xml/resources/ugroups-definition.rnc

plugins/tracker/www/resources/trackers.rng: plugins/tracker/www/resources/trackers.rnc plugins/tracker/www/resources/tracker-definition.rnc plugins/tracker/www/resources/artifact-definition.rnc plugins/tracker/www/resources/triggers.rnc

plugins/tracker/www/resources/tracker.rng: plugins/tracker/www/resources/tracker.rnc plugins/tracker/www/resources/tracker-definition.rng

plugins/tracker/www/resources/artifacts.rng: plugins/tracker/www/resources/artifacts.rnc plugins/tracker/www/resources/artifact-definition.rng

%.rng: %.rnc
	trang -I rnc -O rng $< $@

clean-rng:
	find . -type f -name "*.rng" | xargs rm -f

#
# Tests and all
#

generate-po: ## Generate translatable strings
	@tools/utils/generate-po.sh `pwd`

generate-mo: ## Compile tranlated strings into binary format
	@tools/utils/generate-mo.sh `pwd`

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
	$(DOCKER) run --rm=true -v $(CURDIR):/tuleap enalean/tuleap-test-ut-c5-php51

tests_php53:
	$(DOCKER) run --rm=true -v $(CURDIR):/tuleap enalean/tuleap-test-ut-c6-php53

tests_phpunit:
	$(DOCKER) run -ti --rm=true -v $(CURDIR):/tuleap enalean/tuleap-test-phpunit-c6-php53

phpunit:
	$(PHPUNIT) $(PHPUNIT_TESTS_OPTIONS) --bootstrap tests/phpunit_boostrap.php plugins/proftpd/phpunit

ci_phpunit: composer_update phpunit

simpletest:
	$(SIMPLETEST) $(SIMPLETEST_OPTIONS) tests/simpletest plugins tests/integration

ci_simpletest: simpletest

test: simpletest phpunit api_test

rest_docker_snapshot:
	@$(DOCKER) run -ti --name=rest-init -v $(CURDIR):/tuleap $(DOCKER_REST_TESTS_IMAGE) --init
	@$(DOCKER) commit rest-init $(DOCKER_REST_TESTS_IMGINIT)
	@$(DOCKER) rm -f rest-init
	@echo "Image ready: $(DOCKER_REST_TESTS_IMGINIT)"
	@echo "You can use it like:"
	@echo "# docker run --rm=true -v $(CURDIR):/tuleap $(DOCKER_REST_TESTS_IMGINIT) --run"
	@echo "# docker run --rm=true -v $(CURDIR):/tuleap $(DOCKER_REST_TESTS_IMGINIT) --run tests/rest/ArtifactsTest.php"

rest_docker_clean:
	@$(DOCKER) rmi $(DOCKER_REST_TESTS_IMGINIT)

rest_docker_snap_run:
	@echo "Once inside the container, just run:"
	@echo "# ./run.sh --run tests/rest/UsersTest.php"
	@$(DOCKER) run -ti --rm=true -v $(CURDIR):/tuleap --entrypoint=/bin/bash $(DOCKER_REST_TESTS_IMGINIT) +x

deploy-githooks:
	@if [ -e .git/hooks/pre-commit ]; then\
		echo "pre-commit hook already exists";\
	else\
		hash phpcs 2>/dev/null && {\
			echo "Creating pre-commit hook";\
			ln -s ../../tools/utils/githooks/hook-chain .git/hooks/pre-commit;\
		} || {\
			echo "You need to install phpcs before.";\
			echo "For example on a debian-based environment:";\
			echo "  sudo apt-get install php-pear";\
			echo "  sudo pear install PHP_CodeSniffer";\
			exit 1;\
		};\
	fi

#
# Start development enviromnent with Docker Compose
#

.env:
	@echo "MYSQL_ROOT_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" > .env
	@echo "LDAP_ROOT_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" >> .env
	@echo "LDAP_MANAGER_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" >> .env
	@echo VIRTUAL_HOST=tuleap-web.tuleap-aio-dev.docker >> .env

dev-setup: .env deploy-githooks ## Setup environment for Docker Compose (should only be run once)
	@echo "Create all data containers"
	@$(DOCKER) inspect tuleap_ldap_data > /dev/null 2>&1 || $(DOCKER) run -t --name=tuleap_ldap_data -v /data busybox true
	@$(DOCKER) inspect tuleap_db_data > /dev/null 2>&1 || $(DOCKER) run -t --name=tuleap_db_data -v /var/lib/mysql busybox true
	@$(DOCKER) inspect tuleap_es_data > /dev/null 2>&1 || $(DOCKER) run -t --name=tuleap_es_data -v /data busybox true
	@$(DOCKER) inspect tuleap_data > /dev/null 2>&1 || $(DOCKER) run -t --name=tuleap_data -v /data busybox true
	@$(DOCKER) inspect tuleap_reverseproxy_data > /dev/null 2>&1 || $(DOCKER) run -t --name=tuleap_reverseproxy_data -v /reverseproxy_data busybox true
	@$(DOCKER) inspect tuleap_gerrit_data > /dev/null 2>&1 || $(DOCKER) run -t --name=tuleap_gerrit_data -v /data busybox true

show-passwords: ## Display passwords generated for Docker Compose environment
	@$(DOCKER) run --rm --volumes-from tuleap_data busybox cat /data/root/.tuleap_passwd

dev-forgeupgrade: ## Run forgeupgrade in Docker Compose environment
	@$(DOCKER) exec tuleap-web /usr/lib/forgeupgrade/bin/forgeupgrade --config=/etc/tuleap/forgeupgrade/config.ini update

dev-clear-cache: ## Clear caches in Docker Compose environment
	@$(DOCKER) exec tuleap-web /usr/share/tuleap/src/utils/tuleap --clear-caches

start-dns: ## Start dnsdock to be able to reach Docker Compose environment without having to touch /etc/hosts file
	@$(DOCKER) stop dnsdock || true
	@$(DOCKER) rm dnsdock || true
	@$(DOCKER) run -d -v /var/run/docker.sock:/var/run/docker.sock --name dnsdock -p 172.17.42.1:53:53/udp tonistiigi/dnsdock

start-rp:
	@echo "Start reverse proxy"
	@$(DOCKER_COMPOSE) up -d rp

start: ## Start Tuleap Web + LDAP + DB in Docker Compose environment
	@echo "Start Tuleap Web + LDAP + DB"
	@$(DOCKER_COMPOSE) up -d web
	@echo -n "Your instance will be soon available: http://"
	@grep VIRTUAL_HOST .env | cut -d= -f2
	@echo "You might want to type 'make show-passwords' to see site default passwords"

start-php56: ## Start Tuleap web with php56 & nginx18 support - EXPERIMENTAL
	@echo "Start Tuleap in PHP 5.6"
	@$(DOCKER_COMPOSE) -f docker-compose-php56.yml up -d web

start-es:
	@$(DOCKER_COMPOSE) up -d es

env-gerrit: .env
	@grep --quiet GERRIT_SERVER_NAME .env || echo 'GERRIT_SERVER_NAME=tuleap-gerrit.gerrit-tuleap.docker' >> .env

start-gerrit: env-gerrit
	@docker-compose up -d gerrit
	@echo "Gerrit will be available soon at http://`grep GERRIT_SERVER_NAME .env | cut -d= -f2`:8080"

start-all:
	echo "Start all containers (Web, LDAP, DB, Elasticsearch)"
	@$(DOCKER_COMPOSE) up -d
