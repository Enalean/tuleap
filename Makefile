# How to:
# Run the rest tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_api_test
# Run the phpunit tests in Jenkins: make -C tuleap BUILD_ENV=ci ci_phpunit
# Run docker as a priviledged user: make SUDO=sudo ... or make SUDO=pkexec ...

SHELL=/usr/bin/env bash

OS := $(shell uname)
ifeq ($(OS),Darwin)
DOCKER_COMPOSE_FILE=-f docker-compose.yml -f docker-compose-mac.yml
else
DOCKER_COMPOSE_FILE=-f docker-compose.yml
endif

get_ip_addr = `$(DOCKER_COMPOSE) ps -q $(1) | xargs $(DOCKER) inspect -f '{{.NetworkSettings.Networks.tuleap_default.IPAddress}}'`

SUDO=
DOCKER=$(SUDO) docker
DOCKER_COMPOSE=$(SUDO) "$(shell which docker-compose)" $(DOCKER_COMPOSE_FILE)


ifeq ($(MODE),Prod)
COMPOSER_INSTALL=composer --quiet install --classmap-authoritative --no-dev --no-interaction --no-scripts --prefer-dist
else
COMPOSER_INSTALL=composer --quiet install --prefer-dist
endif

XDG_RUNTIME_DIR ?= /tmp

PHP=php
PRELOAD_GENERATOR=$(PHP) $(CURDIR)/tools/utils/preload/generate-preload.php

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
	@find . src/themes/ plugins/ tests/ tools/utils plugins/mediawiki_standalone/additional-packages/ -mindepth 2 -maxdepth 2 -type f -name 'composer.json' -print0 | \
	    xargs -0 -P"`node ./tools/utils/scripts/max-usable-processors.js`" -I{} bash -c 'echo "Processing {}" && cd "`dirname "{}"`" && $(COMPOSER_INSTALL)'

preload:
	@echo "Verify preload validity"
	@$(PHP) \
		-d error_reporting=2147483647 \
		-d opcache.enable_cli=1 \
		-d display_errors=1 \
		-d display_startup_errors=1 \
		-d opcache.lockfile_path="$(XDG_RUNTIME_DIR)" \
		-d memory_limit=256M \
		-d opcache.preload=$(CURDIR)/tools/utils/preload/verification-loader.php \
		 tools/utils/preload/check-preload.php

## RNG generation

.PHONY: rnc2rng
rnc2rng: clean-rng ## Compile rnc file into rng
	nix-shell ./tools/utils/nix/rnc2rng.nix --pure --command 'make rnc2rng-exec'

.PHONY: rnc2rng-exec
rnc2rng-exec: src/common/xml/resources/project/project.rng \
	 src/common/xml/resources/users.rng  \
	 plugins/svn/resources/svn.rng \
	 plugins/docman/resources/docman.rng \
	 src/common/xml/resources/ugroups.rng \
	 plugins/tracker/resources/tracker.rng \
	 plugins/tracker/resources/trackers.rng \
	 plugins/tracker/resources/artifacts.rng \
	 plugins/agiledashboard/resources/xml_project_agiledashboard.rng \
	 plugins/kanban/resources/kanban.rng \
	 plugins/cardwall/resources/xml_project_cardwall.rng \
	 plugins/testmanagement/resources/testmanagement.rng \
	 plugins/testmanagement/resources/testmanagement_external_changeset.rng \
	 plugins/testmanagement/resources/testmanagement_external_fields.rng \
	 plugins/timetracking/resources/timetracking.rng \
	 plugins/program_management/resources/program_management.rng

src/common/xml/resources/project/project.rng: src/common/xml/resources/project/project.rnc plugins/tracker/resources/tracker-definition.rnc plugins/mediawiki_standalone/resources/mediawiki-definition.rnc plugins/docman/resources/docman-definition.rnc src/common/xml/resources/ugroups-definition.rnc plugins/svn/resources/svn-definition.rnc src/common/xml/resources/frs-definition.rnc src/common/xml/resources/mediawiki-definition.rnc src/common/xml/resources/project-definition.rnc

plugins/svn/resources/svn.rng: plugins/svn/resources/svn.rnc plugins/svn/resources/svn-definition.rnc

plugins/docman/resources/docman.rng: plugins/docman/resources/docman.rnc plugins/docman/resources/docman-definition.rnc

src/common/xml/resources/ugroups.rng: src/common/xml/resources/ugroups.rnc src/common/xml/resources/ugroups-definition.rnc

plugins/tracker/resources/trackers.rng: plugins/tracker/resources/trackers.rnc plugins/tracker/resources/tracker-definition.rnc plugins/tracker/resources/artifact-definition.rnc plugins/tracker/resources/triggers.rnc plugins/tracker/resources/workflow.rnc

plugins/tracker/resources/tracker.rng: plugins/tracker/resources/tracker.rnc plugins/tracker/resources/tracker-definition.rng

plugins/tracker/resources/artifacts.rng: plugins/tracker/resources/artifacts.rnc plugins/tracker/resources/artifact-definition.rng

plugins/timetracking/resources/timetracking.rng: plugins/timetracking/resources/timetracking.rnc plugins/timetracking/resources/timetracking-definition.rng

%.rng: %.rnc
	trang -I rnc -O rng $< $@
	rnginline $@ $@

clean-rng:
	find . -type f -name "*.rng" | xargs rm -f

#
# Templates generation
#

generate-templates: generate-templates-plugins ## Generate XML templates
	xsltproc tools/utils/setup_templates/generate-templates/generate-agile_alm.xml \
		-o tools/utils/setup_templates/agile_alm/agile_alm_template.xml
	cp tools/utils/setup_templates/generate-templates/trackers/testmanagement.xml \
		tools/utils/setup_templates/agile_alm/testmanagement_generated.xml
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

post-checkout-build: composer preload generate-mo generate-templates js-build ## Rebuild the application, can be run without stack up

post-checkout-reload-env: dev-clear-cache dev-forgeupgrade restart-services ## Clear caches, forgeupgrade and restart services

post-checkout: post-checkout-build post-checkout-reload-env ## Clear caches, run forgeupgrade, build assets and generate language files

.PHONY: js-build
js-build:
	pnpm install
	pnpm run build

redeploy-nginx: ## Redeploy nginx configuration
	@$(DOCKER_COMPOSE) exec web tuleap-cfg site-deploy:nginx
	@$(DOCKER_COMPOSE) exec web systemctl restart nginx

restart-services: redeploy-nginx ## Restart nginx, apache and fpm
	@$(DOCKER_COMPOSE) exec web systemctl restart tuleap-php-fpm
	@$(DOCKER_COMPOSE) exec web systemctl restart httpd

generate-po: ## Generate translatable strings
	@tools/utils/generate-po.php `pwd` "$(PLUGIN)"

generate-mo: ## Compile translated strings into binary format
	@tools/utils/generate-mo.sh `pwd`

tests-rest: ## Run all REST tests. SETUP_ONLY=1 to disable auto run. PHP_VERSION to select the version of PHP to use (81). DB to select the database to use (mysql57, mysql80, mariadb103)
	$(eval PHP_VERSION ?= 81)
	$(eval DB ?= mysql80)
	$(eval SETUP_ONLY ?= 0)
	$(eval TESTS_RESULT ?= ./test_results_rest_$(PHP_VERSION)_$(DB))
	SETUP_ONLY="$(SETUP_ONLY)" TESTS_RESULT="$(TESTS_RESULT)" tests/rest/bin/run-compose.sh "$(PHP_VERSION)" "$(DB)"

tests-db: ## Run all DB integration tests. SETUP_ONLY=1 to disable auto run. PHP_VERSION to select the version of PHP to use (81). DB to select the database to use (mysql57, mariadb103, mysql80)
	$(eval PHP_VERSION ?= 81)
	$(eval DB ?= mysql80)
	$(eval SETUP_ONLY ?= 0)
	SETUP_ONLY="$(SETUP_ONLY)" tests/integration/bin/run-compose.sh "$(PHP_VERSION)" "$(DB)"

tests-e2e: ## Run E2E tests. DB to select the database to use (mysql57, mysql80).
	$(eval DB ?= mysql80)
	@tests/e2e/full/wrap.sh "$(DB)"

tests-e2e-dev: ## Run E2E tests. DB to select the database to use (mysql57, mysql80).
	$(eval DB ?= mysql80)
	@tests/e2e/full/wrap_for_dev_context.sh "$(DB)"

tests_cypress:
	@$(MAKE) --no-print-directory tests-e2e

tests_cypress_dev:
	@$(MAKE) --no-print-directory tests-e2e-dev

ifeq ($(COVERAGE_ENABLED),1)
COVERAGE_PARAMS_PHPUNIT=--coverage-html=/tmp/results/coverage/
endif
phpunit-ci-run:
	$(PHP) -dzend.assertions=1 -d pcov.directory=. -d pcov.exclude='~(vendor|node_modules|tests/(?!(?:lib|phpcs))|plugins/\w+/(?!include)|src/(?!(?:common|core|tuleap-cfg)))~' \
		src/vendor/bin/phpunit \
		-c tests/unit/phpunit.xml \
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

phpunit-ci:
	$(eval COVERAGE_ENABLED ?= 1)
	$(eval PHP_VERSION ?= 81)
	mkdir -p $(WORKSPACE)/results/ut-phpunit/php-$(PHP_VERSION)
	@$(DOCKER) run --rm -v $(CURDIR):/tuleap:ro --network none -v $(WORKSPACE)/results/ut-phpunit/php-$(PHP_VERSION):/tmp/results ghcr.io/enalean/tuleap-test-phpunit:c7-php$(PHP_VERSION) make -C /tuleap TARGET="phpunit-ci-run COVERAGE_ENABLED=$(COVERAGE_ENABLED)" PHP=/opt/remi/php$(PHP_VERSION)/root/usr/bin/php run-as-owner

.PHONY: tests-unit-php
tests-unit-php: ## Run PHPUnit unit tests in a Docker container. PHP_VERSION to select the version of PHP to use (81). FILES to run specific tests.
	$(eval PHP_VERSION ?= 81)
	@$(DOCKER) run --rm -v $(CURDIR):/tuleap:ro --network none ghcr.io/enalean/tuleap-test-phpunit:c7-php$(PHP_VERSION) scl enable php$(PHP_VERSION) "make -C /tuleap phpunit FILES=$(FILES)"

ifneq ($(origin SEED),undefined)
    RANDOM_ORDER_SEED_ARGUMENT=--random-order-seed=$(SEED)
endif
phpunit:
	$(PHP) -dzend.assertions=1 src/vendor/bin/phpunit -c tests/unit/phpunit.xml --do-not-cache-result --random-order $(RANDOM_ORDER_SEED_ARGUMENT) $(FILES)

.PHONY:psalm
psalm: ## Run Psalm (PHP static analysis tool). Use FILES variables to execute on a given set of files or directories.
	$(PHP) ./src/vendor/bin/psalm --show-info=false -c=tests/psalm/psalm.xml $(FILES)

.PHONY:psalm-with-info
psalm-with-info: ## Run Psalm (PHP static analysis tool) with INFO findings. Use FILES variables to execute on a given set of files or directories.
	$(eval THREADS ?= 2)
	$(PHP) ./src/vendor/bin/psalm --show-info=true -c=tests/psalm/psalm.xml $(FILES)

.PHONY:psalm-taint-analysis
psalm-taint-analysis: ## Run Psalm (PHP static analysis tool) taint analysis. Use FILES variables to execute on a given set of files or directories.
	$(PHP) ./src/vendor/bin/psalm --memory-limit=4096M --threads=1 --no-cache --taint-analysis -c=tests/psalm/psalm.xml $(FILES)

.PHONY:psalm-unused-code
psalm-unused-code: ## Run Psalm (PHP static analysis tool) detection of unused code. Use FILES variables to execute on a given set of files or directories.
	$(PHP) ./src/vendor/bin/psalm --find-unused-code -c=tests/psalm/psalm.xml $(FILES)

.PHONY:psalm-baseline-update
psalm-baseline-update: ## Update the baseline used by Psalm (PHP static analysis tool).
	$(PHP) ./src/vendor/bin/psalm -c=./tests/psalm/psalm.xml --update-baseline

.PHONY:psalm-baseline-create-from-scratch
psalm-baseline-create-from-scratch: ## Recreate the Psalm baseline from scratch, should only be used when needed when upgrading Psalm.
	$(PHP) -d display_errors=1 -d display_startup_errors=1 -d memory_limit=-1 \
	    ./src/vendor/bin/psalm --no-cache --use-ini-defaults --set-baseline=./tests/psalm/tuleap-baseline.xml -c=./tests/psalm/psalm.xml

phpcs: ## Execute PHPCS with the "strict" ruleset. Use FILES parameter to execute on specific file or directory.
	$(eval FILES ?= .)
	@$(PHP) -d memory_limit=1024M ./src/vendor/bin/phpcs --extensions=php,phpstub --encoding=utf-8 --standard=tests/phpcs/tuleap-ruleset-minimal.xml -s -p $(FILES)

phpcbf: ## Execute PHPCBF with the "strict" ruleset enforced on all the codebase. Use FILES parameter to execute on specific file or directory.
	$(eval FILES ?= .)
	@$(PHP) -d memory_limit=1024M ./src/vendor/bin/phpcbf --extensions=php,phpstub --encoding=utf-8 --standard=tests/phpcs/tuleap-ruleset-minimal.xml -p $(FILES)

deptrac: ## Execute deptrac. Use SEARCH_PATH to look for deptrac config files under a specific path.
	@PHP=$(PHP) ./tests/deptrac/run.sh

eslint: ## Execute eslint. Use FILES parameter to execute on specific file or directory.
	$(eval FILES ?= .)
	@pnpm run eslint --quiet -- $(FILES)

eslint-fix: ## Execute eslint with --fix to try to fix problems automatically. Use FILES parameter to execute on specific file or directory.
	$(eval FILES ?= .)
	@pnpm run eslint --fix --quiet -- $(FILES)

stylelint: ## Execute stylelint. Use FILES parameter to execute on specific files.
	$(eval FILES ?= **/*.{vue,scss})
	@pnpm run stylelint -- $(FILES)

bash-web: ## Give a bash on web container
	@$(DOCKER) exec -e COLUMNS="`tput cols`" -e LINES="`tput lines`" -ti `$(DOCKER_COMPOSE) ps -q web` bash

.PHONY:pull-docker-images
pull-docker-images: ## Pull all docker images used for development
	@$(MAKE) --no-print-directory docker-pull-verify IMAGE_NAME=ghcr.io/enalean/tuleap-test-phpunit:c7-php81 KEY_PATH=tools/utils/signing-keys/tuleap-additional-tools.pub
	@$(MAKE) --no-print-directory docker-pull-verify IMAGE_NAME=ghcr.io/enalean/tuleap-test-rest:c7-php81 KEY_PATH=tools/utils/signing-keys/tuleap-additional-tools.pub
	@$(MAKE) --no-print-directory docker-pull-verify IMAGE_NAME=tuleap/tuleap-community-edition:latest KEY_PATH=tools/utils/signing-keys/tuleap-community.pub
	$(DOCKER_COMPOSE) pull web db redis mailhog ldap
	cosign verify --key=tools/utils/signing-keys/tuleap-additional-tools.pub ghcr.io/enalean/tuleap-aio-dev:c7-php81-nginx
	cosign verify --key=tools/utils/signing-keys/tuleap-additional-tools.pub ghcr.io/enalean/ldap:latest

.PHONY:docker-pull-verify
docker-pull-verify:
	$(DOCKER) pull $(IMAGE_NAME)
	cosign verify --key $(KEY_PATH) $(IMAGE_NAME)

.PHONY:scan-vuln-deps ## Scan dependencies for known vulnerabilities
scan-vuln-deps:
	osv-scanner --recursive --config ./tools/utils/osv-scanner/config.toml .

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

show-passwords: ## Display passwords generated for Docker Compose environment
	@$(DOCKER_COMPOSE) exec web cat /data/root/.tuleap_passwd

show-ips: ## Display ips of all running services
	@$(DOCKER_COMPOSE) ps -q | while read cid; do\
		name=`$(DOCKER) inspect -f '{{.Name}}' $$cid | sed -e 's/^\/tuleap_\(.*\)_1$$/\1/'`;\
		ip=`$(DOCKER) inspect -f '{{.NetworkSettings.Networks.tuleap_default.IPAddress}}' $$cid`;\
		echo "$$ip $$name";\
	done

dev-forgeupgrade: ## Run forgeupgrade in Docker Compose environment
	@$(DOCKER_COMPOSE) exec web /usr/bin/tuleap-cfg site-deploy:forgeupgrade

dev-clear-cache: ## Clear caches in Docker Compose environment
	@$(DOCKER_COMPOSE) exec web /usr/share/tuleap/src/utils/tuleap --clear-caches

start: ## Start Tuleap with PHP 8.1 on CentOS 7
	@echo "Start Tuleap with PHP 8.1 on CentOS 7"
	@$(MAKE) --no-print-directory start-rp

start-el9: ## Start Tuleap with PHP 8.1 on Rocky Linux 9
	@echo "Start Tuleap with PHP 8.1 on Rocky Linux 9"
	@$(MAKE) --no-print-directory start-rp DOCKER_COMPOSE_FLAGS="-f compose-el9.yaml"

start-rp:
	$(eval DOCKER_COMPOSE_FLAGS ?= )
	$(DOCKER_COMPOSE) $(DOCKER_COMPOSE_FLAGS) up --build -d reverse-proxy
	@echo "Update tuleap-web.tuleap-aio-dev.docker in /etc/hosts with: $(call get_ip_addr,reverse-proxy)"
	@echo "Database IP: $(call get_ip_addr,db)"
	@echo "Mailhog (email catch all) available at browser at http://$(call get_ip_addr,mailhog):8025"

start-ldap-admin: ## Start ldap administration ui
	@echo "Start ldap administration ui"
	@$(DOCKER_COMPOSE) up -d ldap-admin
	@echo "Open your browser at https://localhost:6443"

start-gitlab:
	@echo "Start gitlab instance for your Tuleap dev"
	$(DOCKER_COMPOSE) up --build -d gitlab
	@echo "You should update your own /etc/hosts with: "
	@echo "$(call get_ip_addr,gitlab) gitlab.local"

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
	@echo "Jenkins is running at https://tuleap-web.tuleap-aio-dev.docker/jenkins"
	@sleep 1
	@$(DOCKER_COMPOSE) exec -T -u 0 jenkins /usr/local/bin/register_certificate.sh
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

switch-to-mysql80:
	$(eval DB80 := $(shell $(DOCKER_COMPOSE) ps -q db))
	$(DOCKER_COMPOSE) exec db57 sh -c 'exec mysqldump --all-databases -uroot -p"$$MYSQL_ROOT_PASSWORD"' | $(DOCKER) exec -i $(DB80) sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -f'
	$(DOCKER_COMPOSE) exec db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"'
	$(DOCKER_COMPOSE) restart db
	$(DOCKER_COMPOSE) stop db57
	@echo "Data were migrated to MySQL 8.0"

load-mariadb: # Works only with tuleap DB ATM (not mediawiki)
	$(eval MARIADB := $(shell $(DOCKER_COMPOSE) ps -q db-maria-10.3))
	$(DOCKER_COMPOSE) exec db57 sh -c 'exec mysqldump -uroot -p"$$MYSQL_ROOT_PASSWORD" tuleap' 2>/dev/null 1>all.sql
	$(DOCKER) exec -i $(MARIADB) sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "Create database tuleap DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;"'
	$(DOCKER) exec -i $(MARIADB) sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" tuleap' < all.sql
	$(DOCKER_COMPOSE) exec db-maria-10.3 sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"'
	@echo "Data were migrated to MariaDB 10.3, you now need to update /etc/tuleap/conf/database.inc in web container to set `sys_dbhost` to 'db-maria-10.3'"
