RPM_TMP=$(HOME)/rpmbuild
PKG_NAME=tuleap-plugin-timetracking
VERSION=$(shell LANG=C cat VERSION)
# This meant to avoid having git in the docker container
# RELEASE is computed by launcher (for instance jenkins) where git is installed
# and the passed as an absolute value
ifeq ($(RELEASE),)
	RELEASE=1
endif
DIST=
BASE_DIR=$(shell pwd)
RPMBUILD=rpmbuild --define "_topdir $(RPM_TMP)" --define "dist $(DIST)"

NAME_VERSION=$(PKG_NAME)-$(VERSION)

all:
	$(MAKE) DIST=$(shell rpm --eval '.el%{centos_ver}') rpm

rpm: $(RPM_TMP)/RPMS/noarch/$(NAME_VERSION)-$(RELEASE)$(DIST).noarch.rpm
	@echo "Results: $^"

$(RPM_TMP)/RPMS/noarch/$(NAME_VERSION)-$(RELEASE)$(DIST).noarch.rpm: $(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz $(RPM_TMP)/SPECS/$(PKG_NAME).spec
	$(RPMBUILD) -bb $(RPM_TMP)/SPECS/$(PKG_NAME).spec

$(RPM_TMP)/SPECS/$(PKG_NAME).spec: $(BASE_DIR)/$(PKG_NAME).spec
	cat $< | \
		sed -e 's/@@TULEAP_VERSION@@/$(TULEAP_VERSION)/g' |\
		sed -e 's/@@VERSION@@/$(VERSION)/g' |\
		sed -e 's/@@RELEASE@@/$(RELEASE)/g' \
		> $@

$(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz: $(RPM_TMP)
	[ -h $(RPM_TMP)/SOURCES/$(NAME_VERSION) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(NAME_VERSION)
	[ ! -d $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets ] || rm -rf $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets
		cp -ar $(BASE_DIR)/../../src/www/assets/timetracking $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets
	cd $(RPM_TMP)/SOURCES && \
		find $(NAME_VERSION)/ \(\
		-path $(NAME_VERSION)/tests -o\
		-path $(NAME_VERSION)/rpm -o\
		-name "node_modules" -o\
		-name '*.scss' -o\
		-name 'Makefile' -o\
		-name 'build-rpm.sh' -o\
		-name ".git" -o\
		-name ".gitignore" -o\
		-name ".gitmodules" -o\
		-name "*~" -o\
		-path "*/.DS_Store"-o\
		-path "nbproject"-o\
		\)\
		-prune -o -print |\
		 cpio -o -H ustar --quiet |\
		 gzip > $(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz

$(RPM_TMP):
	@[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP

docker-run:
	@[ -n "$(GID)" -a -n "$(UID)" ] || (echo "*** ERROR: UID or GID are missing" && false)
	useradd -d /build -m build
	cp -Rf /tuleap/ /build/src && chown -R build /build/src
	su --login --command "make -C /build/src/plugins/timetracking all TULEAP_VERSION=$(shell LANG=C cat /tuleap/VERSION) RELEASE=$(RELEASE)" build
	install -o $(UID) -g $(GID) -m 0644 /build/rpmbuild/RPMS/noarch/*.rpm /output
