RPM_TMP=$(HOME)/rpmbuild
PKG_NAME=tuleap-plugin-trafficlights
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

FRONTEND_NAME=$(PKG_NAME)-frontend
BACKEND_NAME=$(PKG_NAME)-backend

FRONTEND_NAME_VERSION=$(FRONTEND_NAME)-$(VERSION)
BACKEND_NAME_VERSION=$(BACKEND_NAME)-$(VERSION)

ifeq ($(DIST),.el5)
    RPMBUILD += --define "APP_NAME codendi"
endif

all:
	$(MAKE) DIST=.el5 rpm
	$(MAKE) DIST=.el6 rpm

rpm: $(RPM_TMP)/RPMS/noarch/$(FRONTEND_NAME_VERSION)-$(RELEASE)$(DIST).noarch.rpm $(RPM_TMP)/RPMS/noarch/$(BACKEND_NAME_VERSION)-$(RELEASE)$(DIST).noarch.rpm
	@echo "Results: $^"

$(RPM_TMP)/RPMS/noarch/%.noarch.rpm: $(RPM_TMP)/SRPMS/%.src.rpm
	$(RPMBUILD) --rebuild $<

$(RPM_TMP)/SRPMS/%-$(VERSION)-$(RELEASE)$(DIST).src.rpm: $(RPM_TMP)/SPECS/%.spec $(RPM_TMP)/SOURCES/%-$(VERSION).tar.gz
	$(RPMBUILD) -bs $(RPM_TMP)/SPECS/$*.spec

$(RPM_TMP)/SPECS/%.spec: $(BASE_DIR)/%.spec
	cat $< | \
		sed -e 's/@@VERSION@@/$(VERSION)/g' |\
		sed -e 's/@@RELEASE@@/$(RELEASE)/g' \
		> $@

$(RPM_TMP)/SOURCES/$(BACKEND_NAME_VERSION).tar.gz: $(RPM_TMP)
	[ -h $(RPM_TMP)/SOURCES/$(BACKEND_NAME_VERSION) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(BACKEND_NAME_VERSION)
	cd $(RPM_TMP)/SOURCES && \
		find $(BACKEND_NAME_VERSION)/ \(\
		-path $(BACKEND_NAME_VERSION)/www/scripts/angular -o\
		-path $(BACKEND_NAME_VERSION)/tests -o\
		-name '*.spec' -o\
		-name 'Makefile' -o\
		-name ".git" -o\
		-name ".gitignore" -o\
		-name ".gitmodules" -o\
		-name "*~" -o\
		-path "*/.DS_Store"-o\
		-path "nbproject"-o\
		\)\
		-prune -o -print |\
		 cpio -o -H ustar --quiet |\
		 gzip > $(RPM_TMP)/SOURCES/$(BACKEND_NAME_VERSION).tar.gz

$(RPM_TMP)/SOURCES/$(FRONTEND_NAME_VERSION).tar.gz: $(RPM_TMP)
	[ -h $(RPM_TMP)/SOURCES/$(FRONTEND_NAME_VERSION) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(FRONTEND_NAME_VERSION)
	cd $(RPM_TMP)/SOURCES && \
		find $(FRONTEND_NAME_VERSION)/www/scripts/angular \(\
		-path "*/.DS_Store" -o\
		-path "*/bin" -o\
		-path "*/build" -o\
		-path "*/node_modules" -o\
		-path "*/vendor"\
		\)\
		-prune -o -print |\
		 cpio -o -H ustar --quiet |\
		 gzip > $(RPM_TMP)/SOURCES/$(FRONTEND_NAME_VERSION).tar.gz

$(RPM_TMP):
	@[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP

docker-run:
	@[ -n "$(GID)" -a -n "$(UID)" ] || (echo "*** ERROR: UID or GID are missing" && false)
	useradd -d /build -m build
	su --login --command "make -C /app all RELEASE=$(RELEASE)" build
	install -o $(UID) -g $(GID) -m 0644 /build/rpmbuild/RPMS/noarch/*.rpm /output
