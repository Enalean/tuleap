# How to:
# Run the rest tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_api_test
# Run the phpunit tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_phpunit
# Run docker as a priviledged user: make SUDO=sudo ... or make SUDO=pkexec ...

OS := $(shell uname)
ifeq ($(OS),Darwin)
DOCKER_COMPOSE_FILE=-f docker-compose.yml -f docker-compose-mac.yml
else
DOCKER_COMPOSE_FILE=-f docker-compose.yml
endif

get_ip_addr = `$(DOCKER_COMPOSE) ps -q $(1) | xargs docker inspect -f '{{.NetworkSettings.Networks.tuleap_default.IPAddress}}'`

SUDO=
DOCKER=$(SUDO) docker
DOCKER_COMPOSE=$(SUDO) docker-compose $(DOCKER_COMPOSE_FILE)

ifeq ($(MODE),Prod)
COMPOSER_INSTALL=composer --quiet install --classmap-authoritative --no-dev --no-interaction --no-scripts --prefer-dist
else
COMPOSER_INSTALL=composer --quiet install --prefer-dist
endif

PHP=php

AUTOLOAD_EXCLUDES=^tests|^template

.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z0-9_\-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo "(Other less used targets are available, open Makefile for details)"

#
# Utilities
#

.PHONY: composer
composer:  ## Install PHP dependencies with Composer
	@find . plugins/ src/themes/ src/www/themes/ tools/ tests/ -mindepth 2 -maxdepth 2 -type f -name 'composer.json' -print0 | \
	    xargs -0 -P"`node ./tools/utils/scripts/max-usable-processors.js`" -L1 -I{} bash -c 'echo "Processing {}" && cd "`dirname "{}"`" && $(COMPOSER_INSTALL)'

## RNG generation

rnc2rng-docker: clean-rng ## Compile rnc file into rng
	@$(DOCKER) run --rm=true -v $(CURDIR):/tuleap:cached -e USER=`id -u` -e GROUP=`id -g` enalean/tuleap-dev-swissarmyknife:2 --rnc2rng

rnc2rng: src/common/xml/resources/project/project.rng \
	 src/common/xml/resources/users.rng  \
	 plugins/svn/resources/svn.rng \
	 plugins/docman/resources/docman.rng \
	 src/common/xml/resources/ugroups.rng \
	 plugins/tracker/resources/tracker.rng \
	 plugins/tracker/resources/trackers.rng \
	 plugins/tracker/resources/artifacts.rng \
	 plugins/agiledashboard/resources/xml_project_agiledashboard.rng \
	 plugins/cardwall/resources/xml_project_cardwall.rng \
	 plugins/testmanagement/resources/testmanagement.rng \
	 plugins/testmanagement/resources/testmanagement_external_changeset.rng \
	 plugins/testmanagement/resources/testmanagement_external_fields.rng

src/common/xml/resources/project/project.rng: src/common/xml/resources/project/project.rnc plugins/tracker/resources/tracker-definition.rnc plugins/docman/resources/docman-definition.rnc src/common/xml/resources/ugroups-definition.rnc plugins/svn/resources/svn-definition.rnc src/common/xml/resources/frs-definition.rnc src/common/xml/resources/mediawiki-definition.rnc src/common/xml/resources/project-definition.rnc

plugins/svn/resources/svn.rng: plugins/svn/resources/svn.rnc plugins/svn/resources/svn-definition.rnc

plugins/docman/resources/docman.rng: plugins/docman/resources/docman.rnc plugins/docman/resources/docman-definition.rnc

src/common/xml/resources/ugroups.rng: src/common/xml/resources/ugroups.rnc src/common/xml/resources/ugroups-definition.rnc

plugins/tracker/resources/trackers.rng: plugins/tracker/resources/trackers.rnc plugins/tracker/resources/tracker-definition.rnc plugins/tracker/resources/artifact-definition.rnc plugins/tracker/resources/triggers.rnc plugins/tracker/resources/workflow.rnc

plugins/tracker/resources/tracker.rng: plugins/tracker/resources/tracker.rnc plugins/tracker/resources/tracker-definition.rng

plugins/tracker/resources/artifacts.rng: plugins/tracker/resources/artifacts.rnc plugins/tracker/resources/artifact-definition.rng

%.rng: %.rnc
	trang -I rnc -O rng $< $@

clean-rng:
	find . -type f -name "*.rng" | xargs rm -f

#
# Templates generation
#

generate-templates-docker: ## Generate XML templates
	@$(DOCKER) run --rm -u "`id -u`":"`id -g`" -v "$(CURDIR):/wrk" enalean/xsltproc make generate-templates

generate-templates: generate-templates-plugins
	xsltproc tools/utils/setup_templates/generate-templates/generate-agile_alm.xml \
		-o tools/utils/setup_templates/agile_alm/agile_alm_template.xml
	xsltproc tools/utils/setup_templates/generate-templates/generate-kanban.xml \
		-o tools/utils/setup_templates/kanban/kanban_template.xml

generate-templates-plugins:
	@find . plugins/ -mindepth 2 -maxdepth 2 -type f -name 'Makefile' | while read file; do \
	    basedir=`dirname $$file`; \
	    make -C $$basedir -sq generate-templates 2>/dev/null; \
		if [ $$? -eq 1 ]; then \
			$(MAKE) -C $$basedir generate-templates; \
		fi \
	done

#
# Tests and all
#

post-checkout-build: composer generate-mo generate-templates-docker npm-build ## Rebuild the application, can be run without stack up

post-checkout-reload-env: dev-clear-cache dev-forgeupgrade restart-services ## Clear caches, forgeupgrade and restart services

post-checkout: post-checkout-build post-checkout-reload-env ## Clear caches, run forgeupgrade, build assets and generate language files

npm-build:
	npm install
	npm run build

redeploy-nginx: ## Redeploy nginx configuration
	@$(DOCKER_COMPOSE) exec web /usr/share/tuleap/tools/utils/php73/run.php --module=nginx
	@$(DOCKER_COMPOSE) exec web service nginx restart

restart-services: redeploy-nginx ## Restart nginx, apache and fpm
	@$(DOCKER_COMPOSE) exec web service php73-php-fpm restart
	@$(DOCKER_COMPOSE) exec web service httpd restart

generate-po: ## Generate translatable strings
	@tools/utils/generate-po.php `pwd` "$(PLUGIN)"

generate-mo: ## Compile translated strings into binary format
	@tools/utils/generate-mo.sh `pwd`

tests_rest_php73:
	$(MAKE) tests-rest DB=mysql57

tests_rest_setup_73:
	$(MAKE) tests-rest DB=mysql57 SETUP_ONLY=1

tests-rest: ## Run all REST tests. SETUP_ONLY=1 to disable auto run. PHP_VERSION to select the version of PHP to use (73, 74). DB to select the database to use (mysql57, mariadb103)
	$(eval PHP_VERSION ?= 73)
	$(eval DB ?= mysql57)
	$(eval SETUP_ONLY ?= 0)
	SETUP_ONLY="$(SETUP_ONLY)" tests/rest/bin/run-compose.sh "$(PHP_VERSION)" "$(DB)"

tests_soap_73:
	$(MAKE) tests-soap DB=mysql57

tests-soap: ## Run all SOAP tests. PHP_VERSION to select the version of PHP to use (73, 74). DB to select the database to use (mysql57, mariadb103)
	$(eval PHP_VERSION ?= 73)
	$(eval DB ?= mysql57)
	SETUP_ONLY="$(SETUP_ONLY)" tests/soap/bin/run-compose.sh "$(PHP_VERSION)" "$(DB)"

tests_db_73:
	$(MAKE) tests-rest DB=mysql57

tests-db: ## Run all DB integration tests with PHP 7.3. SETUP_ONLY=1 to disable auto run. PHP_VERSION to select the version of PHP to use (73, 74). DB to select the database to use (mysql57, mariadb103)
	$(eval PHP_VERSION ?= 73)
	$(eval DB ?= mysql57)
	$(eval SETUP_ONLY ?= 0)
	SETUP_ONLY="$(SETUP_ONLY)" tests/integration/bin/run-compose.sh "$(PHP_VERSION)" "$(DB)"

tests_cypress: ## Run Cypress tests
	@tests/e2e/full/wrap.sh

tests_cypress_dev: ## Start cypress container to launch tests manually
	@tests/e2e/full/wrap_for_dev_context.sh

tests_cypress_distlp: ## Run Cypress distlp tests
	@tests/e2e/distlp/wrap.sh

ifeq ($(COVERAGE_ENABLED),1)
COVERAGE_PARAMS_PHPUNIT=--coverage-html=/tmp/results/coverage/
endif
phpunit-ci-run:
	$(PHP) -d pcov.directory=. -d pcov.exclude='~(vendor|node_modules|tests/(?!(?:lib|phpcs))|plugins/\w+/(?!include))~' \
		src/vendor/bin/phpunit \
		-c tests/phpunit/phpunit.xml \
		--log-junit /tmp/results/phpunit_tests_results.xml \
		$(COVERAGE_PARAMS_PHPUNIT) \
		--random-order \
		--do-not-cache-result

run-as-owner:
	@USER_ID=`stat -c '%u' /tuleap`; \
	GROUP_ID=`stat -c '%g' /tuleap`; \
	groupadd -g $$GROUP_ID runner; \
	useradd -u $$USER_ID -g $$GROUP_ID runner
	su -c "$(MAKE) -C $(CURDIR) $(TARGET) PHP=$(PHP)" -l runner

phpunit-ci-73:
	$(eval COVERAGE_ENABLED ?= 1)
	mkdir -p $(WORKSPACE)/results/ut-phpunit/php-73
	@docker run --rm -v $(CURDIR):/tuleap:ro -v $(WORKSPACE)/results/ut-phpunit/php-73:/tmp/results --network none enalean/tuleap-test-phpunit:c7-php73 make -C /tuleap TARGET="phpunit-ci-run COVERAGE_ENABLED=$(COVERAGE_ENABLED)" PHP=/opt/remi/php73/root/usr/bin/php run-as-owner

phpunit-docker-73: ## Run PHPUnit tests in Docker container with PHP 7.3. Use FILES parameter to run specific tests.
	@docker run --rm -v $(CURDIR):/tuleap:ro --network none enalean/tuleap-test-phpunit:c7-php73 scl enable php73 "make -C /tuleap phpunit FILES=$(FILES)"

phpunit-ci-74:
	$(eval COVERAGE_ENABLED ?= 1)
	mkdir -p $(WORKSPACE)/results/ut-phpunit/php-74
	@docker run --rm -v $(CURDIR):/tuleap:ro --network none -v $(WORKSPACE)/results/ut-phpunit/php-74:/tmp/results enalean/tuleap-test-phpunit:c7-php74 make -C /tuleap TARGET="phpunit-ci-run COVERAGE_ENABLED=$(COVERAGE_ENABLED)" PHP=/opt/remi/php74/root/usr/bin/php run-as-owner

phpunit-docker-74: ## Run PHPUnit tests in Docker container with PHP 7.4. Use FILES parameter to run specific tests.
	@docker run --rm -v $(CURDIR):/tuleap:ro --network none enalean/tuleap-test-phpunit:c7-php74 scl enable php74 "make -C /tuleap phpunit FILES=$(FILES)"

ifneq ($(origin SEED),undefined)
    RANDOM_ORDER_SEED_ARGUMENT=--random-order-seed=$(SEED)
endif
phpunit:
	$(PHP) src/vendor/bin/phpunit -c tests/phpunit/phpunit.xml --do-not-cache-result --random-order $(RANDOM_ORDER_SEED_ARGUMENT) $(FILES)

simpletest-73-ci:
	@mkdir -p $(WORKSPACE)/results/ut-simpletest/php-73
	@docker run --rm -v $(CURDIR):/tuleap:ro,cached --mount type=tmpfs,destination=/tmp -v $(WORKSPACE)/results/ut-simpletest/php-73:/output:rw --network none -u $(id -u):$(id -g) enalean/tuleap-simpletest:c6-php73 /opt/remi/php73/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php --log-junit=/output/results.xml run \
	/tuleap/tests/simpletest \
	/tuleap/plugins/

simpletest-73: ## Run SimpleTest with PHP 7.3
	@docker run --rm -v $(CURDIR):/tuleap:ro,cached --mount type=tmpfs,destination=/tmp --network none -u $(id -u):$(id -g) enalean/tuleap-simpletest:c6-php73 /opt/remi/php73/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php run \
	/tuleap/tests/simpletest \
	/tuleap/plugins/

simpletest-73-file: ## Run SimpleTest with PHP 7.3 on a given file or directory with FILE variable
	@docker run --rm -v $(CURDIR):/tuleap:ro --network none -u $(id -u):$(id -g) enalean/tuleap-simpletest:c6-php73 /opt/remi/php73/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php run $(FILE)

simpletest-74-ci:
	@mkdir -p $(WORKSPACE)/results/ut-simpletest/php-74
	@docker run --rm -v $(CURDIR):/tuleap:ro,cached --mount type=tmpfs,destination=/tmp -v $(WORKSPACE)/results/ut-simpletest/php-74:/output:rw -u $(id -u):$(id -g) enalean/tuleap-simpletest:c7-php74 /opt/remi/php74/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php --log-junit=/output/results.xml run \
	/tuleap/tests/simpletest \
	/tuleap/plugins/

simpletest-74: ## Run SimpleTest with PHP 7.4
	@docker run --rm -v $(CURDIR):/tuleap:ro,cached --mount type=tmpfs,destination=/tmp -u $(id -u):$(id -g) enalean/tuleap-simpletest:c7-php74 /opt/remi/php74/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php run \
	/tuleap/tests/simpletest \
	/tuleap/plugins/

simpletest-74-file: ## Run SimpleTest with PHP 7.4 on a given file or directory with FILE variable
	@docker run --rm -v $(CURDIR):/tuleap:ro -u $(id -u):$(id -g) enalean/tuleap-simpletest:c7-php74 /opt/remi/php74/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php run $(FILE)


psalm: ## Run Psalm (PHP static analysis tool). Use FILES variables to execute on a given set of files or directories.
	$(PHP) tests/psalm/psalm-config-plugins-git-ignore.php tests/psalm/psalm.xml ./src/vendor/bin/psalm --show-info=false -c={config_path} $(FILES)

psalm-with-info: ## Run Psalm (PHP static analysis tool) with INFO findings. Use FILES variables to execute on a given set of files or directories.
	$(eval THREADS ?= 2)
	$(PHP) tests/psalm/psalm-config-plugins-git-ignore.php tests/psalm/psalm.xml ./src/vendor/bin/psalm --show-info=true -c={config_path} $(FILES)

psalm-baseline-update: ## Update the baseline used by Psalm (PHP static analysis tool).
	$(eval TMPPSALM := $(shell mktemp -d))
	git checkout-index -a --prefix="$(TMPPSALM)/"
	$(MAKE) -C "$(TMPPSALM)/" composer npm-build
	pushd "$(TMPPSALM)"; \
	$(PHP) ./src/vendor/bin/psalm -c=./tests/psalm/psalm.xml --update-baseline; \
	popd
	cp -f "$(TMPPSALM)"/tests/psalm/tuleap-baseline.xml ./tests/psalm/tuleap-baseline.xml
	rm -rf "$(TMPPSALM)"

psalm-baseline-create-from-scratch: ## Recreate the Psalm baseline from scratch, should only be used when needed when upgrading Psalm.
	$(eval TMPPSALM := $(shell mktemp -d))
	git checkout-index -a --prefix="$(TMPPSALM)/"
	rm "$(TMPPSALM)"/tests/psalm/tuleap-baseline.xml
	$(MAKE) -C "$(TMPPSALM)/" composer npm-build
	pushd "$(TMPPSALM)"; \
	$(PHP) -d display_errors=1 -d display_startup_errors=1 -d memory_limit=-1 \
	    ./src/vendor/bin/psalm --no-cache --use-ini-defaults --set-baseline=./tests/psalm/tuleap-baseline.xml -c=./tests/psalm/psalm.xml; \
	popd
	cp -f "$(TMPPSALM)"/tests/psalm/tuleap-baseline.xml ./tests/psalm/tuleap-baseline.xml
	rm -rf "$(TMPPSALM)"

phpcs: ## Execute PHPCS with the "strict" ruleset. Use FILES parameter to execute on specific file or directory.
	$(eval FILES ?= .)
	@$(PHP) -d memory_limit=512M ./src/vendor/bin/phpcs --extensions=php,phpstub --encoding=utf-8 --standard=tests/phpcs/tuleap-ruleset-minimal.xml -s -p $(FILES)

phpcbf: ## Execute PHPCBF with the "strict" ruleset enforced on all the codebase. Use FILES parameter to execute on specific file or directory.
	$(eval FILES ?= .)
	@$(PHP) -d memory_limit=512M ./src/vendor/bin/phpcbf --extensions=php,phpstub --encoding=utf-8 --standard=tests/phpcs/tuleap-ruleset-minimal.xml -p $(FILES)

deptrac: ## Execute deptrac. Use CONFIG to choose the configuration file to evaluate (default to tests/deptrac/core_on_plugins.yml).
	$(eval CONFIG ?= tests/deptrac/core_on_plugins.yml)
	@$(PHP) ./src/vendor/bin/deptrac analyze --no-banner -- $(CONFIG)

eslint: ## Execute eslint. Use FILES parameter to execute on specific file or directory.
	$(eval FILES ?= .)
	@npm run eslint -- --quiet $(FILES)

eslint-fix: ## Execute eslint with --fix to try to fix problems automatically. Use FILES parameter to execute on specific file or directory.
	$(eval FILES ?= .)
	@npm run eslint -- --fix --quiet $(FILES)

bash-web: ## Give a bash on web container
	@docker exec -e COLUMNS="`tput cols`" -e LINES="`tput lines`" -ti `docker-compose ps -q web` bash

#
# Dev setup
#

deploy-githooks:
	@if [ -e .git/hooks/pre-commit ]; then\
		echo "pre-commit hook already exists";\
	else\
		{\
			echo "Creating pre-commit hook";\
			ln -s ../../tools/utils/githooks/hook-chain .git/hooks/pre-commit;\
		};\
	fi

#
# Start development enviromnent with Docker Compose
#

dev-setup: .env deploy-githooks ## Setup environment for Docker Compose (should only be run once)

.env:
	@echo "MYSQL_ROOT_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" > .env
	@echo "LDAP_ROOT_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" >> .env
	@echo "LDAP_MANAGER_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" >> .env
	@echo VIRTUAL_HOST=tuleap-web.tuleap-aio-dev.docker >> .env
	@echo "REALTIME_KEY=$(head -c 64 /dev/urandom | base64 --wrap=88)" >> .env

show-passwords: ## Display passwords generated for Docker Compose environment
	@$(DOCKER_COMPOSE) exec web cat /data/root/.tuleap_passwd

show-ips: ## Display ips of all running services
	@$(DOCKER_COMPOSE) ps -q | while read cid; do\
		name=`docker inspect -f '{{.Name}}' $$cid | sed -e 's/^\/tuleap_\(.*\)_1$$/\1/'`;\
		ip=`docker inspect -f '{{.NetworkSettings.Networks.tuleap_default.IPAddress}}' $$cid`;\
		echo "$$ip $$name";\
	done

dev-forgeupgrade: ## Run forgeupgrade in Docker Compose environment
	@$(DOCKER_COMPOSE) exec web /usr/lib/forgeupgrade/bin/forgeupgrade --config=/etc/tuleap/forgeupgrade/config.ini update

dev-clear-cache: ## Clear caches in Docker Compose environment
	@$(DOCKER_COMPOSE) exec web /usr/share/tuleap/src/utils/tuleap --clear-caches

start-php73-centos7: ## Start Tuleap web with php73 & nginx on CentOS7
	@echo "Start Tuleap in PHP 7.3 on CentOS 7"
	@$(DOCKER_COMPOSE) -f docker-compose-centos7.yml up --build -d reverse-proxy
	@echo "Update tuleap-web.tuleap-aio-dev.docker in /etc/hosts with: $(call get_ip_addr,reverse-proxy)"

start-php73 start: ## Start Tuleap web with php73 & nginx
	@echo "Start Tuleap in PHP 7.3"
	@$(DOCKER_COMPOSE) up --build -d reverse-proxy
	@echo "Update tuleap-web.tuleap-aio-dev.docker in /etc/hosts with: $(call get_ip_addr,reverse-proxy)"

start-distlp:
	@echo "Start Tuleap with reverse-proxy, backend web and backend svn"
	-@$(DOCKER_COMPOSE) stop
	@$(DOCKER_COMPOSE) -f docker-compose-distlp.yml up -d reverse-proxy-distlp
	@echo "Add '$(call get_ip_addr,reverse-proxy) tuleap-web.tuleap-aio-dev.docker' to /etc/hosts"
	@echo "Ensure $(call get_ip_addr,reverse-proxy) is configured as sys_trusted_proxies in /etc/tuleap/conf/local.inc"
	@echo "You can access :"
	@echo "* Reverse proxy with: docker-compose -f docker-compose.yml -f -f docker-compose-distlp.yml reverse-proxy-distlp bash"
	@echo "* Backend web with: docker-compose -f docker-compose.yml -f -f docker-compose-distlp.yml backend-web bash"
	@echo "* Backend SVN with: docker-compose -f docker-compose.yml -f -f docker-compose-distlp.yml backend-svn bash"

start-ldap-admin: ## Start ldap administration ui
	@echo "Start ldap administration ui"
	@docker-compose up -d ldap-admin
	@echo "Open your browser at https://localhost:6443"

start-mailhog: ## Start mailhog to catch emails sent by your Tuleap dev platform
	@echo "Start mailhog to catch emails sent by your Tuleap dev platform"
	$(DOCKER_COMPOSE) up -d mailhog
	$(DOCKER_COMPOSE) exec web make -C /usr/share/tuleap deploy-mailhog-conf
	@echo "Open your browser at http://$(call get_ip_addr,mailhog):8025"

deploy-mailhog-conf:
	@if ! grep -q -F -e '^relayhost = mailhog:1025' /etc/postfix/main.cf; then \
	    sed -i -e 's/^\(transport_maps.*\)$$/#\1/' /etc/postfix/main.cf && \
	    echo 'relayhost = mailhog:1025' >> /etc/postfix/main.cf; \
	    service postfix restart; \
	 fi

stop-distlp:
	@$(SUDO) docker-compose -f docker-compose-distlp.yml stop

start-gerrit:
	@$(DOCKER_COMPOSE) up -d gerrit
	@echo "You should update /etc/hosts with: "
	@echo "$(call get_ip_addr,gerrit) gerrit.tuleap-aio-dev.docker"
	@echo "Gerrit will be available soon at http://gerrit.tuleap-aio-dev.docker:8080"
	@echo "If you need to setup gerrit, see instructions in tools/utils/gerrit_setup/Readme.md"

show-gerrit-ssh-pub-key:
	@$(DOCKER_COMPOSE) exec gerrit cat /data/.ssh/id_rsa.pub

start-jenkins:
	@$(DOCKER_COMPOSE) up -d jenkins
	@echo "Jenkins is running at http://$(call get_ip_addr,jenkins):8080"
	@if $(DOCKER_COMPOSE) exec jenkins test -f /var/jenkins_home/secrets/initialAdminPassword; then \
		echo "Admin credentials are admin `$(DOCKER_COMPOSE) exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword`"; \
	else \
		echo "Admin credentials will be prompted by jenkins during start-up"; \
	fi

start-redis:
	@$(DOCKER_COMPOSE) up -d redis

start-all:
	echo "Start all containers (Web, LDAP, DB, Elasticsearch)"
	@$(DOCKER_COMPOSE) up -d

switch-to-mysql57:
	$(eval DB57 := $(shell $(DOCKER_COMPOSE) ps -q db))
	$(DOCKER_COMPOSE) exec db55 sh -c 'exec mysqldump --all-databases  -uroot -p"$$MYSQL_ROOT_PASSWORD"' | $(DOCKER) exec -i $(DB57) sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD"'
	$(DOCKER_COMPOSE) exec db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"'
	@echo "Data were migrated to MySQL 5.7"

load-mariadb: # Works only with tuleap DB ATM (not mediawiki)
	$(eval MARIADB := $(shell $(DOCKER_COMPOSE) ps -q db-maria-10.3))
	$(DOCKER_COMPOSE) exec db57 sh -c 'exec mysqldump -uroot -p"$$MYSQL_ROOT_PASSWORD" tuleap' 2>/dev/null 1>all.sql
	$(DOCKER) exec -i $(MARIADB) sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "Create database tuleap DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;"'
	$(DOCKER) exec -i $(MARIADB) sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" tuleap' < all.sql
	$(DOCKER_COMPOSE) exec db-maria-10.3 sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"'
	@echo "Data were migrated to MariaDB 10.3, you now need to update /etc/tuleap/conf/database.inc in web container to set `sys_dbhost` to 'db-maria-10.3'"
