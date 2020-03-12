SHELL := /bin/bash
RPM_TMP=$(HOME)/rpmbuild
PKG_NAME=tuleap-plugin-testmanagement
VERSION=$(shell LANG=C cat VERSION)
# This meant to avoid having git in the docker container
# RELEASE is computed by launcher (for instance jenkins) where git is installed
# and the passed as an absolute value
ifeq ($(RELEASE),)
	RELEASE=1
endif

BASE_DIR=$(shell pwd)
RPMBUILD=rpmbuild --define "_topdir $(RPM_TMP)"

NAME_VERSION=$(PKG_NAME)-$(VERSION)

PHP=php

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
build:
	cd /build/src && npm install && \
	cd /build/src/src/www/themes/common/tlp && npm install && npm run build && \
	cd /build/src/src/www/scripts && npm install && npm run build && \
	cd /build/src/plugins/tracker/scripts && npm install && npm run build && \
	cd /build/src/plugins/testmanagement/scripts && npm install && npm run build && \
	cd /build/src/plugins/testmanagement/ && npm install && npm run build && \
	cd /build/src/plugins/testmanagement/ && composer install --classmap-authoritative --no-dev --no-interaction --no-scripts

$(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz: build $(RPM_TMP)
	[ -h $(RPM_TMP)/SOURCES/$(NAME_VERSION) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(NAME_VERSION)
	[ ! -d $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets ] || rm -rf $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets
	cp -ar $(BASE_DIR)/../../src/www/assets/testmanagement $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets
	cd $(RPM_TMP)/SOURCES && \
	    find $(NAME_VERSION)/ \(\
	    -path $(NAME_VERSION)/tests -o\
	    -name 'node_modules' -o\
	    -name '*.spec' -o\
	    -name 'Makefile' -o\
	    -name 'build-rpm.sh' -o\
	    -path $(NAME_VERSION)/www/scripts/angular -o\
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
	@[ -n "$(GID)" -a -n "$(UID)" ] || (echo "*** ERROR: UID or GID are missing" && false)
	useradd -d /build -m build
	pushd /tuleap && git checkout-index -a --prefix=/build/src/ && popd
	cp -Rf /plugin/ /build/src/plugins/testmanagement && chown -R build /build/src
	su --login --command "make -C /build/src/plugins/testmanagement all RELEASE=$(RELEASE)" build
	install -o $(UID) -g $(GID) -m 0644 /build/rpmbuild/RPMS/noarch/*.rpm /output

psalm: ## Run Psalm (PHP static analysis tool). Use FILES variables to execute on a given set of files or directories.
	$(PHP) ../../src/vendor/bin/psalm --show-info=false -c=tests/psalm/psalm.xml $(FILES)

psalm-with-info: ## Run Psalm (PHP static analysis tool) with INFO findings. Use FILES variables to execute on a given set of files or directories.
	$(PHP) ../../src/vendor/bin/psalm --show-info=true -c=tests/psalm/psalm.xml $(FILES)
