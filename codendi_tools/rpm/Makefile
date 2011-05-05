ifeq ($(strip $(RPM_TMP)),)
	RPM_TMP=$(HOME)/rpmbuild
endif

BASE_DIR=$(shell cd ../..; pwd)

version=$(shell LANG=C cat $(BASE_DIR)/VERSION)

CORE_MAILMAN_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/CORE_MAILMAN_VERSION)
CORE_SUBVERSION_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/CORE_SUBVERSION_VERSION)
CORE_CVS_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/CORE_CVS_VERSION)

PLUGIN_FORUMML_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/forumml/VERSION)
PLUGIN_GIT_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/git/VERSION)
PLUGIN_SVNTODIMENSIONS_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/svntodimensions/VERSION)
PLUGIN_CVSTODIMENSIONS_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/cvstodimensions/VERSION)
PLUGIN_DOCMANWATERMARK_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/docmanwatermark/VERSION)
PLUGIN_LDAP_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/ldap/VERSION)
PLUGIN_IM_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/IM/VERSION)
PLUGIN_JRI_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/codendijri/VERSION)
PLUGIN_ECLIPSE_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/eclipse/VERSION)
PLUGIN_HUDSON_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/hudson/VERSION)
PLUGIN_WEBDAV_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/webdav/VERSION)

THEME_CODEX_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/CodeX/VERSION)
THEME_CODEXTAB_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/CodeXTab/VERSION)
THEME_DAWN_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/Dawn/VERSION)
THEME_SAVANNAH_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/savannah/VERSION)
THEME_STTAB_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/STTab/VERSION)
THEME_CODEXSTN_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/CodexSTN/VERSION)
THEME_STEERFORGE_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/SteerForge/VERSION)

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
	cd $(BASE_DIR) && find . -type f -or -type l  | grep -v '/.svn/' | egrep -v '^\./plugins/tests' | egrep -v '^\./plugins/maillog' | cpio -pdumB --quiet $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version)
	cd $(RPM_TMP)/SOURCES && tar czf $(PKG_NAME)-$(version).tar.gz $(PKG_NAME)-$(version)

codendi: rpmprep tarball
	cat codendi.spec |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@CORE_MAILMAN_VERSION@@/$(CORE_MAILMAN_VERSION)/g' |\
		sed -e 's/@@CORE_SUBVERSION_VERSION@@/$(CORE_SUBVERSION_VERSION)/g' |\
		sed -e 's/@@CORE_CVS_VERSION@@/$(CORE_CVS_VERSION)/g' |\
		sed -e 's/@@PLUGIN_FORUMML_VERSION@@/$(PLUGIN_FORUMML_VERSION)/g' |\
		sed -e 's/@@PLUGIN_GIT_VERSION@@/$(PLUGIN_GIT_VERSION)/g' |\
		sed -e 's/@@PLUGIN_SVNTODIMENSIONS_VERSION@@/$(PLUGIN_SVNTODIMENSIONS_VERSION)/g' |\
		sed -e 's/@@PLUGIN_CVSTODIMENSIONS_VERSION@@/$(PLUGIN_CVSTODIMENSIONS_VERSION)/g' |\
		sed -e 's/@@PLUGIN_DOCMANWATERMARK_VERSION@@/$(PLUGIN_DOCMANWATERMARK_VERSION)/g' |\
		sed -e 's/@@PLUGIN_LDAP_VERSION@@/$(PLUGIN_LDAP_VERSION)/g' |\
		sed -e 's/@@PLUGIN_IM_VERSION@@/$(PLUGIN_IM_VERSION)/g' |\
		sed -e 's/@@PLUGIN_JRI_VERSION@@/$(PLUGIN_JRI_VERSION)/g' |\
		sed -e 's/@@PLUGIN_ECLIPSE_VERSION@@/$(PLUGIN_ECLIPSE_VERSION)/g' |\
		sed -e 's/@@PLUGIN_HUDSON_VERSION@@/$(PLUGIN_HUDSON_VERSION)/g' |\
		sed -e 's/@@PLUGIN_WEBDAV_VERSION@@/$(PLUGIN_WEBDAV_VERSION)/g' |\
		sed -e 's/@@THEME_CODEX_VERSION@@/$(THEME_CODEX_VERSION)/g' |\
		sed -e 's/@@THEME_CODEXTAB_VERSION@@/$(THEME_CODEXTAB_VERSION)/g' |\
		sed -e 's/@@THEME_DAWN_VERSION@@/$(THEME_DAWN_VERSION)/g' |\
		sed -e 's/@@THEME_SAVANNAH_VERSION@@/$(THEME_SAVANNAH_VERSION)/g' |\
		sed -e 's/@@THEME_STTAB_VERSION@@/$(THEME_STTAB_VERSION)/g' |\
		sed -e 's/@@THEME_CODEXSTN_VERSION@@/$(THEME_CODEXSTN_VERSION)/g' |\
		sed -e 's/@@THEME_STEERFORGE_VERSION@@/$(THEME_STEERFORGE_VERSION)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/codendi.spec
	rpmbuild -bb $(RPM_TMP)/SPECS/codendi.spec

custom_codendi: tarball
	cat codendi-customization.spec |\
		sed -e 's/@@PLATFORM@@/default/' |\
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

all: clean codendi custom_codendi codendi-all-deps

dist:
	mkdir -p $(RPM_TMP)/yum
	cp -ar $(RPM_TMP)/RPMS/* $(RPM_TMP)/yum
	createrepo $(RPM_TMP)/yum
