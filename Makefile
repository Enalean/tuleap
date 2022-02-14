SHELL := /usr/bin/env bash
RPM_TMP=/build/rpmbuild
PKG_NAME=tuleap-plugin-baseline
VERSION=$(shell LANG=C cat VERSION)
# This meant to avoid having git in the docker container
# RELEASE is computed by launcher (for instance jenkins) where git is installed
# and the passed as an absolute value
ifeq ($(RELEASE),)
	RELEASE=1
endif

BASE_DIR=$(shell pwd)
RPMBUILD=rpmbuild --define "_topdir $(RPM_TMP)" --define "_tmppath /build"

NAME_VERSION=$(PKG_NAME)-$(VERSION)

compute_version: ## Compute the package version as in the RPM package and in Tuleap Admin interface
	@PACKAGE_VERSION="$$(cat VERSION | tr -d '[[:space:]]')";\
		RELEASE=1;\
		LAST_TAG="$$(git describe --abbrev=0 --tags)";\
		if [ "$$LAST_TAG" == "$$PACKAGE_VERSION" ]; then\
    		NB_COMMITS=$$(git log --oneline "$$LAST_TAG"..HEAD | wc -l);\
    		if [ $$NB_COMMITS -gt 0 ]; then\
	    		RELEASE=$$(($$NB_COMMITS + 1));\
    		fi;\
		fi;\
	echo $$PACKAGE_VERSION-$$RELEASE

deploy-githooks:
	@if [ -e .git/hooks/pre-commit ]; then\
		echo "pre-commit hook already exists";\
	else\
		{\
			echo "Creating pre-commit hook";\
			ln -s ../../tools/utils/githooks/hook-chain .git/hooks/pre-commit;\
		};\
	fi

install:
	# This will install phpcs in src folder.
	# Required by tuleap/tools/utils/githooks/pre-commit-02-phpcs, which is executed by
	# baseline git hook chain.
	composer install --working-dir=src/
	pnpm install
	cp ../../.eslintrc.js .

all:
	$(MAKE) rpm

rpm: $(RPM_TMP)/RPMS/noarch/$(NAME_VERSION)-$(RELEASE).noarch.rpm
	@echo "Results: $^"

$(RPM_TMP)/RPMS/noarch/%.noarch.rpm: $(RPM_TMP)/SRPMS/%.src.rpm
	$(RPMBUILD) --rebuild $<

$(RPM_TMP)/SRPMS/%-$(VERSION)-$(RELEASE).src.rpm: $(RPM_TMP)/SPECS/%.spec $(RPM_TMP)/SOURCES/%-$(VERSION).tar.gz
	$(RPMBUILD) -bs $(RPM_TMP)/SPECS/$*.spec

$(RPM_TMP)/SPECS/%.spec: $(BASE_DIR)/%.spec
	cat $< | \
		sed -e 's/@@VERSION@@/$(VERSION)/g' |\
		sed -e 's/@@RELEASE@@/$(RELEASE)/g' \
		> $@

.PHONY: build
build: export HOME = /build
build: export TMPDIR = /build
build:
	cd /build/src && CYPRESS_INSTALL_BINARY=0 pnpm install && \
	cd /build/src/ && pnpm run build -- --scope=@tuleap/plugin-baseline --include-dependencies && \
	cd /build/src/plugins/baseline/ && composer install --classmap-authoritative --no-dev --no-interaction --no-scripts

$(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz: build $(RPM_TMP)
	[ -h $(RPM_TMP)/SOURCES/$(NAME_VERSION) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(NAME_VERSION)
	cd $(RPM_TMP)/SOURCES && \
	    find $(NAME_VERSION)/ \(\
	    -path $(NAME_VERSION)/tests -o\
	    -name 'node_modules' -o\
	    -name '*.spec' -o\
	    -name 'Makefile' -o\
	    -name 'build-rpm.sh' -o\
	    -name ".git" -o\
	    -name ".gitignore" -o\
	    -name ".gitmodules" -o\
	    -name "*~" -o\
	    -path "*/.DS_Store"-o\
	    -path "nbproject"-o\
	    \)\
	    -prune -o -print \
		|\
		cpio -o -H ustar --quiet |\
		gzip > $(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz

$(RPM_TMP):
	@[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP

docker-run:
	pushd /tuleap && git checkout-index -a --prefix=/build/src/ && popd
	cp -Rf /plugin/ /build/src/plugins/baseline
	make -C /build/src/plugins/baseline all RELEASE=$(RELEASE)
	install -m 0644 /build/rpmbuild/RPMS/noarch/*.rpm /output

sonarqube-start: ## Start Sonarqube server
	@docker-compose up -d sonarqube
	@echo "Sonarqube is starting.... Go to http://localhost:9000"

sonarqube-stop: ## Start Sonarqube server
	@docker-compose down

sonarqube-analyze: ## Run tests and analyze code with Sonarqube (Sonarqube must be started)
	@rm -Rf phpunit-output && mkdir phpunit-output
	@docker run --rm -v $(CURDIR)/../..:/tuleap:ro -v $(CURDIR)/phpunit-output:/phpunit-output -w /tuleap enalean/tuleap-test-phpunit:c7-php73 scl enable php73 "php -d pcov.directory=. src/vendor/bin/phpunit -c plugins/baseline/phpunit/phpunit.xml --log-junit /phpunit-output/junit.xml --coverage-clover /phpunit-output/clover.xml --do-not-cache-result"
	@sed -i.bak -e "s#<file name=\"/tuleap/plugins/baseline/#<file name=\"#g" phpunit-output/clover.xml
	@cd scripts && pnpm run coverage
	@sed -i.bak -e "s#^SF:.*/tuleap/plugins/baseline/scripts#SF:scripts#g" scripts/coverage/lcov.info
	@docker-compose run sonarscanner
