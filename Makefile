RPM_TMP=$(HOME)/rpmbuild
PKG_NAME=tuleap-plugin-testmgmt
VERSION=$(shell LANG=C cat VERSION)
RELEASE=1
BASE_DIR=$(shell pwd)
RPMBUILD=rpmbuild --define "_topdir $(RPM_TMP)"

all: rpm

rpm: $(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-$(VERSION)-$(RELEASE).noarch.rpm
	@echo "RPM is here: $(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-$(VERSION)-$(RELEASE).noarch.rpm"
	@echo "SRPM is here: $(RPM_TMP)/SRPMS/$(PKG_NAME)-$(VERSION)-$(RELEASE).src.rpm" 

$(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-$(VERSION)-$(RELEASE).noarch.rpm: $(RPM_TMP)/SRPMS/$(PKG_NAME)-$(VERSION)-$(RELEASE).src.rpm
	$(RPMBUILD) --rebuild $<

$(RPM_TMP)/SRPMS/$(PKG_NAME)-$(VERSION)-$(RELEASE).src.rpm: $(RPM_TMP)/SPECS/plugin-testmgmt.spec $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(VERSION).tar.gz
	$(RPMBUILD) -bs $(RPM_TMP)/SPECS/plugin-testmgmt.spec

$(RPM_TMP)/SPECS/plugin-testmgmt.spec: $(BASE_DIR)/plugin-testmgmt.spec
	cat $(BASE_DIR)/plugin-testmgmt.spec | \
		sed -e 's/@@VERSION@@/$(VERSION)/g' |\
		sed -e 's/@@RELEASE@@/$(RELEASE)/g' \
		> $(RPM_TMP)/SPECS/plugin-testmgmt.spec

$(RPM_TMP)/SOURCES/$(PKG_NAME)-$(VERSION).tar.gz: $(RPM_TMP)
	[ -h $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(VERSION) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(VERSION)
	cd $(RPM_TMP)/SOURCES && \
		find $(PKG_NAME)-$(VERSION) \(\
		-name '*.less' -o\
		-name ".git" -o\
		-name ".gitignore" -o\
		-name ".gitmodules" -o\
		-name "*~" -o\
		-path "*/.DS_Store"-o\
		\)\
		-prune -o -print |\
		 cpio -o -H ustar --quiet |\
		 gzip > $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(VERSION).tar.gz

$(RPM_TMP):
	@[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP


