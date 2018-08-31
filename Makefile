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

AUTOLOAD_EXCLUDES=^tests|^template

.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z0-9_\-\ ]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo "(Other less used targets are available, open Makefile for details)"

#
# Utilities
#

doc: ## Build CLI documentation
	$(MAKE) -C documentation all

autoload:
	@echo "Generate core"
	@(cd src/common; phpab -q --compat -o autoload.php --exclude "./wiki/phpwiki/*" .)
	@for path in `ls src/www/themes | egrep -v "^Tuleap|^common|^FlamingParrot|^local"`; do \
	     echo "Generate theme $$path"; \
	     (cd "src/www/themes/$$path/"; phpab -q --compat -o autoload.php .) \
        done;
	@echo "Generate tests"
	@(cd tests/lib; phpab  -q --compat -o autoload.php .)
	@(cd tests/soap/lib; phpab  -q --compat -o autoload.php .)
	@(cd tests/rest/lib; phpab  -q --compat -o autoload.php .)
	@for path in `ls plugins | egrep -v "$(AUTOLOAD_EXCLUDES)"`; do \
		test -f "plugins/$$path/composer.json" && continue; \
		echo "Generate plugin $$path"; \
		(cd "plugins/$$path/include"; phpab -q --compat -o autoload.php .) \
        done;

autoload-with-userid:
	@echo "Generate core"
	@(cd src/common; phpab -q --compat -o autoload.php --exclude "./wiki/phpwiki/*" .;chown $(USER_ID):$(USER_ID) autoload.php)
	@echo "Generate tests"
	@(cd tests/lib; phpab  -q --compat -o autoload.php .;chown $(USER_ID):$(USER_ID) autoload.php)
	@(cd tests/soap/lib; phpab  -q --compat -o autoload.php .)
	@(cd tests/rest/lib; phpab  -q --compat -o autoload.php .)
	@for path in `ls plugins | egrep -v "$(AUTOLOAD_EXCLUDES)"`; do \
		test -f "plugins/$$path/composer.json" && continue; \
		echo "Generate plugin $$path"; \
		(cd "plugins/$$path/include"; phpab -q --compat -o autoload.php .; chown $(USER_ID):$(USER_ID) autoload.php) \
        done;

autoload-docker: ## Generate autoload files
	@$(DOCKER) run --rm=true -v $(CURDIR):/tuleap -e USER=`id -u` -e GROUP=`id -g` enalean/tuleap-dev-swissarmyknife:2 --autoload

autoload-dev:
	@tools/utils/autoload.sh

.PHONY: composer
composer:  ## Install PHP dependencies with Composer
	@echo "Processing src/composer.json"
	@composer install --working-dir=src/
	@find plugins/ tests/ -mindepth 2 -maxdepth 2 -type f -name 'composer.json' \
		-exec echo "Processing {}" \; -execdir composer install \;
	@echo "Processing tools/Configuration/composer.json"
	@composer install --working-dir=tools/Configuration/

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

post-checkout: composer generate-mo dev-clear-cache dev-forgeupgrade npm-build restart-services ## Clear caches, run forgeupgrade, build assets and generate language files

npm-build:
	npm ci
	npm run build

redeploy-nginx: ## Redeploy nginx configuration
	@$(DOCKER_COMPOSE) exec web /usr/share/tuleap/tools/utils/php56/run.php --module=nginx
	@$(DOCKER_COMPOSE) exec web service nginx restart

restart-services: redeploy-nginx ## Restart nginx, apache and fpm
	@$(DOCKER_COMPOSE) exec web service php56-php-fpm restart
	@$(DOCKER_COMPOSE) exec web service httpd restart

generate-po: ## Generate translatable strings
	@tools/utils/generate-po.php `pwd`

generate-mo: ## Compile translated strings into binary format
	@tools/utils/generate-mo.sh `pwd`

tests_rest_56: ## Run all REST tests with PHP FPM 5.6
	$(DOCKER) run -ti --rm -v $(CURDIR):/usr/share/tuleap:ro --mount type=tmpfs,destination=/tmp enalean/tuleap-test-rest:c6-php56-mysql57

tests_rest_72: ## Run all REST tests with PHP FPM 7.2
	$(DOCKER) run -ti --rm -v $(CURDIR):/usr/share/tuleap:ro --mount type=tmpfs,destination=/tmp enalean/tuleap-test-rest:c6-php72-mysql57

tests_soap_56: ## Run all SOAP tests in PHP 5.6
	$(DOCKER) run -ti --rm -v $(CURDIR):/usr/share/tuleap:ro --mount type=tmpfs,destination=/tmp enalean/tuleap-test-soap:3

tests_soap_72: ## Run all SOAP tests in PHP 7.2
	$(DOCKER) run -ti --rm -v $(CURDIR):/usr/share/tuleap:ro --mount type=tmpfs,destination=/tmp enalean/tuleap-test-soap:4

tests_cypress: ## Run Cypress tests
	@tests/e2e/full/wrap.sh

tests_cypress_dev: ## Start cypress container to launch tests manually
	@tests/e2e/full/wrap_for_dev_context.sh

tests_rest_setup_56: ## Start REST tests (PHP FPM 5.6) container to launch tests manually
	$(DOCKER) run -ti --rm -v $(CURDIR):/usr/share/tuleap --mount type=tmpfs,destination=/tmp -w /usr/share/tuleap enalean/tuleap-test-rest:c6-php56-mysql57 bash

tests_rest_setup_72: ## Start REST tests (PHP FPM 7.2) container to launch tests manually
	$(DOCKER) run -ti --rm -v $(CURDIR):/usr/share/tuleap --mount type=tmpfs,destination=/tmp -w /usr/share/tuleap enalean/tuleap-test-rest:c6-php72-mysql57 bash

phpunit-ci-run:
	$(PHP) src/vendor/bin/phpunit \
		-c tests/phpunit/phpunit.xml \
		--log-junit /tmp/results/phpunit_tests_results.xml

run-as-owner:
	@USER_ID=`stat -c '%u' /tuleap`; \
	GROUP_ID=`stat -c '%g' /tuleap`; \
	groupadd -g $$GROUP_ID runner; \
	useradd -u $$USER_ID -g $$GROUP_ID runner
	su -c "$(MAKE) -C $(CURDIR) $(TARGET) PHP=$(PHP)" -l runner

phpunit-ci-56:
	mkdir -p $(WORKSPACE)/results/ut-phpunit/php-56
	@docker run --rm -v $(CURDIR):/tuleap:ro -v $(WORKSPACE)/results/ut-phpunit/php-56:/tmp/results --entrypoint /bin/bash enalean/tuleap-test-phpunit:c6-php56 -c "make -C /tuleap run-as-owner TARGET=phpunit-ci-run PHP=/opt/remi/php56/root/usr/bin/php"

phpunit-ci-72:
	mkdir -p $(WORKSPACE)/results/ut-phpunit/php-72
	@docker run --rm -v $(CURDIR):/tuleap:ro -v $(WORKSPACE)/results/ut-phpunit/php-72:/tmp/results enalean/tuleap-test-phpunit:c6-php72 make -C /tuleap TARGET=phpunit-ci-run PHP=/opt/remi/php72/root/usr/bin/php run-as-owner

phpunit-docker-56:
	@docker run --rm -v $(CURDIR):/tuleap:ro enalean/tuleap-test-phpunit:c6-php56 scl enable php56 "make -C /tuleap phpunit"

phpunit-docker-72:
	@docker run --rm -v $(CURDIR):/tuleap:ro enalean/tuleap-test-phpunit:c6-php72 scl enable php72 "make -C /tuleap phpunit"

phpunit:
	src/vendor/bin/phpunit -c tests/phpunit/phpunit.xml

simpletest-72-ci:
	@mkdir -p $(WORKSPACE)/results/ut-simpletest/php-72
	@docker run --rm -v $(CURDIR):/tuleap:ro -v $(WORKSPACE)/results/ut-simpletest/php-72:/output:rw -u $(id -u):$(id -g) enalean/tuleap-simpletest:c6-php72 /opt/remi/php72/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php --log-junit=/output/results.xml run \
	/tuleap/tests/simpletest \
	/tuleap/plugins/ \
	/tuleap/tests/integration

simpletest-72: ## Run SimpleTest with PHP 7.2
	@docker run --rm -v $(CURDIR):/tuleap:ro -u $(id -u):$(id -g) enalean/tuleap-simpletest:c6-php72 /opt/remi/php72/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php run \
	/tuleap/tests/simpletest \
	/tuleap/plugins/ \
	/tuleap/tests/integration

simpletest-72-file: ## Run SimpleTest with PHP 7.2 on a given file or directory with FILE variable
	@docker run --rm -v $(CURDIR):/tuleap:ro -u $(id -u):$(id -g) enalean/tuleap-simpletest:c6-php72 /opt/remi/php72/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php run $(FILE)

simpletest-56-ci:
	@mkdir -p $(WORKSPACE)/results/ut-simpletest/php-56
	@docker run --rm -v $(CURDIR):/tuleap:ro -v $(WORKSPACE)/results/ut-simpletest/php-56:/output:rw --entrypoint "" enalean/tuleap-simpletest:c6-php56 /opt/remi/php56/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php --log-junit=/output/results.xml run  \
	/tuleap/tests/simpletest \
	/tuleap/plugins/ \
	/tuleap/tests/integration \

simpletest-56: ## Run SimpleTest with PHP 5.6 tests in CLI
	@docker run --rm -v $(CURDIR):/tuleap:ro --entrypoint "" enalean/tuleap-simpletest:c6-php56 /opt/remi/php56/root/usr/bin/php /tuleap/tests/bin/simpletest11x.php run \
	/tuleap/tests/simpletest \
	/tuleap/plugins/ \
	/tuleap/tests/integration \

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
		}
	fi

#
# Start development enviromnent with Docker Compose
#

dev-setup: .env deploy-githooks ## Setup environment for Docker Compose (should only be run once)

.env:
	@echo "MYSQL_ROOT_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" > .env
	@echo "LDAP_ROOT_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" >> .env
	@echo "LDAP_MANAGER_PASSWORD=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" >> .env
	@echo "RABBITMQ_DEFAULT_PASS=`env LC_CTYPE=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32`" >> .env
	@echo RABBITMQ_DEFAULT_USER=tuleap >> .env
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

start-php56 start: ## Start Tuleap web with php56 & nginx
	@echo "Start Tuleap in PHP 5.6"
	@$(DOCKER_COMPOSE) -f docker-compose.yml up --build -d reverse-proxy
	@echo "Update tuleap-web.tuleap-aio-dev.docker in /etc/hosts with: $(call get_ip_addr,reverse-proxy)"

start-php56-centos7: ## Start Tuleap web with php56 & nginx on CentOS7
	@echo "Start Tuleap in PHP 5.6 on CentOS 7"
	@$(DOCKER_COMPOSE) -f docker-compose.yml -f docker-compose-centos7.yml up --build -d reverse-proxy
	@echo "Update tuleap-web.tuleap-aio-dev.docker in /etc/hosts with: $(call get_ip_addr,reverse-proxy)"

start-php72: ## Start Tuleap web with php72 & nginx
	@echo "Start Tuleap in PHP 7.2"
	@$(DOCKER_COMPOSE) -f docker-compose.yml -f docker-compose-php72.yml up --build -d reverse-proxy
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

start-mailhog: # Start mailhog to catch emails sent by your Tuleap dev platform
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

env-gerrit: .env
	@grep --quiet GERRIT_SERVER_NAME .env || echo 'GERRIT_SERVER_NAME=tuleap-gerrit.gerrit-tuleap.docker' >> .env

start-gerrit: env-gerrit
	@docker-compose up -d gerrit
	@echo "Gerrit will be available soon at http://`grep GERRIT_SERVER_NAME .env | cut -d= -f2`:8080"

start-jenkins:
	@$(DOCKER_COMPOSE) up -d jenkins
	@echo "Jenkins is running at http://$(call get_ip_addr,jenkins):8080"
	@if $(DOCKER_COMPOSE) exec jenkins test -f /var/jenkins_home/secrets/initialAdminPassword; then \
		echo "Admin credentials are admin `$(DOCKER_COMPOSE) exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword`"; \
	else \
		echo "Admin credentials will be prompted by jenkins during start-up"; \
	fi

start-all:
	echo "Start all containers (Web, LDAP, DB, Elasticsearch)"
	@$(DOCKER_COMPOSE) up -d
