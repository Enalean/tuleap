ifeq ($(strip $(RPM_TMP)),)
	RPM_TMP=~/rpmbuild
endif

#BUILDRESULT=${PWD}/fusionforge_repo
BASE_DIR=$(shell cd ../..; pwd)
version=$(shell LANG=C cat $(BASE_DIR)/ST-VERSION)

PKG_NAME=codendi_st

rpmprep:
	mkdir -p $(RPM_TMP)
	cd $(RPM_TMP) && mkdir -p BUILD RPMS SOURCES SPECS SRPMS TMP
	echo "%_topdir $(RPM_TMP)" > ~/.rpmmacros
	echo '%_tmppath %{_topdir}/TMP' >> ~/.rpmmacros
	echo '%_buildroot %{_tmppath}/%{name}-root' >> ~/.rpmmacros
	echo '%_sysconfdir /etc' >> ~/.rpmmacros
	#echo '%dist -zata' >> ~/.rpmmacros

tarball:
	cd $(BASE_DIR) && find . -type f -or -type l | grep -v '/.svn/' | cpio -pdumB --quiet $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version)
	cd $(RPM_TMP)/SOURCES && tar czf $(PKG_NAME)-$(version).tar.gz $(PKG_NAME)-$(version)

codendi: rpmprep tarball
	cat codendi.spec |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/codendi.spec
	rpmbuild -bb $(RPM_TMP)/SPECS/codendi.spec

custom_codendi: tarball
	cat codendi-customization.spec |\
		sed -e 's/@@PLATFORM@@/codendi/' |\
		sed -e 's/@@SYS_DEFAULT_DOMAIN@@/codendi.org/' |\
		sed -e 's/@@SYS_HTTPS_HOST@@/codendi.org/' |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/codendi-customization.spec
	cp *_ParametersLocal.dtd $(RPM_TMP)/SOURCES/
	cp $(BASE_DIR)/src/www/themes/common/images/organization_logo.png $(RPM_TMP)/SOURCES/organization_logo.png
	rpmbuild -bb $(RPM_TMP)/SPECS/codendi-customization.spec

codendi-all-deps: codendi-all-deps.spec
	cat $< |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/$<
	cp README.all-deps $(RPM_TMP)/SOURCES/
	rpmbuild -bb $(RPM_TMP)/SPECS/$<

#
# Rebuild codendi's rpms
#
# Only mailman as of today
core_rpms:
	cp -ar $(BASE_DIR)/rpm/SPECS/* $(RPM_TMP)/SPECS
	cp -ar $(BASE_DIR)/rpm/SOURCES/* $(RPM_TMP)/SOURCES
	cd $(RPM_TMP)/SPECS && $(MAKE) mailman.codendi

clean:
	-rm -Rf $(RPM_TMP)/*
	-rm -Rf ~/.rpmmacros
	-rm -f /tmp/docbook-cug-*
	-rm -f /tmp/log_xml2html_*
	-rm -f /tmp/log_xml2pdf_*

all: clean codendi core_rpms custom_codendi codendi-all-deps

dist:
	mkdir -p $(RPM_TMP)/yum
	cp -ar $(RPM_TMP)/RPMS/* $(RPM_TMP)/yum
	createrepo $(RPM_TMP)/yum
