RPM_TMP=$(HOME)/rpmbuild
PHP_BASE=php
BASE_DIR=$(shell cd ../..; pwd)
SHELL=/bin/bash

version=$(shell LANG=C cat $(BASE_DIR)/VERSION)

CORE_MAILMAN_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/CORE_MAILMAN_VERSION)
CORE_CVS_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/CORE_CVS_VERSION)
CORE_REST_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/api/VERSION)

PLUGIN_FORUMML_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/forumml/VERSION)
PLUGIN_GIT_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/git/VERSION)
PLUGIN_LDAP_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/ldap/VERSION)
PLUGIN_IM_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/IM/VERSION)
PLUGIN_HUDSON_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/hudson/VERSION)
PLUGIN_HUDSON_SVN_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/hudson_svn/VERSION)
PLUGIN_HUDSON_GIT_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/hudson_git/VERSION)
PLUGIN_WEBDAV_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/webdav/VERSION)
PLUGIN_TRACKER_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/tracker/VERSION)
PLUGIN_GRAPHONTRACKERS_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/graphontrackersv5/VERSION)
PLUGIN_CARDWALL_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/cardwall/VERSION)
PLUGIN_AGILEDASHBOARD_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/agiledashboard/VERSION)
PLUGIN_FULLTEXTSEARCH_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/fulltextsearch/VERSION)
PLUGIN_ARCHIVEDELETEDITEMS_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/archivedeleteditems/VERSION)
PLUGIN_FUSIONFORGE_COMPAT_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/fusionforge_compat/VERSION)
PLUGIN_ADMSSW_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/admssw/VERSION)
PLUGIN_DOAPRDF_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/doaprdf/VERSION)
PLUGIN_FOAFPROFILES_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/foafprofiles/VERSION)
PLUGIN_MEDIAWIKI_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/mediawiki/VERSION)
PLUGIN_BOOMERANG_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/boomerang/VERSION)
PLUGIN_OPENIDCONNECTCLIENT_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/openidconnectclient/VERSION)
PLUGIN_PROFTPD_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/proftpd/VERSION)
PLUGIN_SVN_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/svn/VERSION)
PLUGIN_FRS_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/frs/VERSION)
PLUGIN_REFERENCEALIAS_CORE_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/referencealias_core/VERSION)
PLUGIN_REFERENCEALIAS_TRACKER_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/referencealias_tracker/VERSION)
PLUGIN_REFERENCEALIAS_MEDIAWIKI_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/referencealias_mediawiki/VERSION)
PLUGIN_REFERENCEALIAS_SVN_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/referencealias_svn/VERSION)
PLUGIN_REFERENCEALIAS_GIT_VERSION=$(shell LANG=C cat $(BASE_DIR)/plugins/referencealias_git/VERSION)

THEME_TULEAP_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/Tuleap/VERSION)
THEME_FLAMINGPARROT_VERSION=$(shell LANG=C cat $(BASE_DIR)/src/www/themes/FlamingParrot/VERSION)

ifdef GIT_BRANCH
TAG_RELEASE = $(subst -, ,$(GIT_BRANCH))
RELEASE = $(word 2, $(TAG_RELEASE))
endif

ifeq ($(strip $(RELEASE)),)
RELEASE=1
endif

PKG_NAME=tuleap

DIST=

ifeq ($(strip $(OS)),rhel6)
SPEC_FILE=tuleap.rhel6.spec
CUSTOM_SPEC_FILE=customization.rhel6.spec
endif

.PHONY: all clean rpmprep rpm custom_rpm rpm-all-deps tuleap-all tarball sass node srpms dist

.PRECIOUS: $(RPM_TMP) $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz

.SECONDARY:

default: all

all: srpms
	rpmbuild --rebuild $(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-deps-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-customization-default-$(version)-$(RELEASE)$(DIST).src.rpm

##
## Setup environment
##

rpmprep: $(RPM_TMP) ~/.rpmmacros

~/.rpmmacros:
	@echo "Creating ~/.rpmmacros"
	@echo "%_topdir $(RPM_TMP)" > ~/.rpmmacros
	@echo '%_tmppath %{_topdir}/TMP' >> ~/.rpmmacros
	@echo '%_buildroot %{_tmppath}/%{name}-root' >> ~/.rpmmacros
	@echo '%_sysconfdir /etc' >> ~/.rpmmacros
	@echo '%php_base $(PHP_BASE)' >> ~/.rpmmacros
	@echo '%undefine dist' >> ~/.rpmmacros

$(RPM_TMP):
	[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP

##
## Build tarball
##

tarball: rpmprep $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz

sass:
	$(MAKE) -C $(BASE_DIR) sass

node:
	cd $(BASE_DIR)/plugins/agiledashboard/www/js/planning-v2 &&\
		npm install &&\
		bower install --allow-root --config.interactive=false &&\
		npm run build &&\
	cd $(BASE_DIR)/plugins/agiledashboard/www/js/kanban &&\
		npm install &&\
		bower install --allow-root --config.interactive=false &&\
		npm run build &&\
	cd $(BASE_DIR)/plugins/frs/www/js/angular &&\
		npm install &&\
		bower install --allow-root --config.interactive=false &&\
		npm run build

$(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz: rpmprep sass
	[ -h $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version)
	cd $(RPM_TMP)/SOURCES && \
		{ \
			find $(PKG_NAME)-$(version)/ \(\
				-path '*/.svn' -o\
				-name '*.less' -o\
				-name '*.scss' -o\
				-name ".git" -o\
				-name ".gitignore" -o\
				-name ".gitmodules" -o\
				-path "*/.DS_Store" -o\
				-path $(PKG_NAME)-$(version)/plugins/tests -o\
				-path $(PKG_NAME)-$(version)/plugins/agiledashboard/www/js/planning-v2 -o\
				-path $(PKG_NAME)-$(version)/plugins/agiledashboard/www/js/kanban \)\
				-prune -o -print \
			&& \
			find $(PKG_NAME)-$(version)/plugins/agiledashboard/www/js/planning-v2/bin; \
			find $(PKG_NAME)-$(version)/plugins/agiledashboard/www/js/kanban/bin; \
		} | \
		cpio -o -H ustar --quiet |\
		gzip > $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz

##
## Build RPM
##

rpm: tarball $(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).noarch.rpm

srpms: clean rpmprep tarball $(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-deps-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-customization-default-$(version)-$(RELEASE)$(DIST).src.rpm

srpms-docker: clean rpmprep node tarball $(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-deps-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).src.rpm $(RPM_TMP)/SRPMS/$(PKG_NAME)-customization-default-$(version)-$(RELEASE)$(DIST).src.rpm

$(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).noarch.rpm: $(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm
	rpmbuild --rebuild $<

$(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm: $(SPEC_FILE) $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz
	cat $(SPEC_FILE) |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@RELEASE@@/$(RELEASE)/g' |\
		sed -e 's/@@CORE_MAILMAN_VERSION@@/$(CORE_MAILMAN_VERSION)/g' |\
		sed -e 's/@@CORE_CVS_VERSION@@/$(CORE_CVS_VERSION)/g' |\
		sed -e 's/@@CORE_REST_VERSION@@/$(CORE_REST_VERSION)/g' |\
		sed -e 's/@@PLUGIN_FORUMML_VERSION@@/$(PLUGIN_FORUMML_VERSION)/g' |\
		sed -e 's/@@PLUGIN_GIT_VERSION@@/$(PLUGIN_GIT_VERSION)/g' |\
		sed -e 's/@@PLUGIN_LDAP_VERSION@@/$(PLUGIN_LDAP_VERSION)/g' |\
		sed -e 's/@@PLUGIN_IM_VERSION@@/$(PLUGIN_IM_VERSION)/g' |\
		sed -e 's/@@PLUGIN_HUDSON_VERSION@@/$(PLUGIN_HUDSON_VERSION)/g' |\
		sed -e 's/@@PLUGIN_HUDSON_SVN_VERSION@@/$(PLUGIN_HUDSON_SVN_VERSION)/g' |\
		sed -e 's/@@PLUGIN_HUDSON_GIT_VERSION@@/$(PLUGIN_HUDSON_GIT_VERSION)/g' |\
		sed -e 's/@@PLUGIN_WEBDAV_VERSION@@/$(PLUGIN_WEBDAV_VERSION)/g' |\
		sed -e 's/@@PLUGIN_REQUESTHELP_VERSION@@/$(PLUGIN_REQUESTHELP_VERSION)/g' |\
		sed -e 's/@@PLUGIN_TRACKER_VERSION@@/$(PLUGIN_TRACKER_VERSION)/g' |\
		sed -e 's/@@PLUGIN_GRAPHONTRACKERS_VERSION@@/$(PLUGIN_GRAPHONTRACKERS_VERSION)/g' |\
		sed -e 's/@@PLUGIN_CARDWALL_VERSION@@/$(PLUGIN_CARDWALL_VERSION)/g' |\
		sed -e 's/@@PLUGIN_AGILEDASHBOARD_VERSION@@/$(PLUGIN_AGILEDASHBOARD_VERSION)/g' |\
		sed -e 's/@@PLUGIN_FULLTEXTSEARCH_VERSION@@/$(PLUGIN_FULLTEXTSEARCH_VERSION)/g' |\
		sed -e 's/@@PLUGIN_ARCHIVEDELETEDITEMS_VERSION@@/$(PLUGIN_ARCHIVEDELETEDITEMS_VERSION)/g' |\
		sed -e 's/@@PLUGIN_FUSIONFORGE_COMPAT_VERSION@@/$(PLUGIN_FUSIONFORGE_COMPAT_VERSION)/g' |\
		sed -e 's/@@PLUGIN_ADMSSW_VERSION@@/$(PLUGIN_ADMSSW_VERSION)/g' |\
		sed -e 's/@@PLUGIN_BOOMERANG_VERSION@@/$(PLUGIN_BOOMERANG_VERSION)/g' |\
		sed -e 's/@@PLUGIN_DOAPRDF_VERSION@@/$(PLUGIN_DOAPRDF_VERSION)/g' |\
		sed -e 's/@@PLUGIN_FOAFPROFILES_VERSION@@/$(PLUGIN_FOAFPROFILES_VERSION)/g' |\
		sed -e 's/@@PLUGIN_MEDIAWIKI_VERSION@@/$(PLUGIN_MEDIAWIKI_VERSION)/g' |\
		sed -e 's/@@PLUGIN_OPENIDCONNECTCLIENT_VERSION@@/$(PLUGIN_OPENIDCONNECTCLIENT_VERSION)/g' |\
		sed -e 's/@@PLUGIN_PROFTPD_VERSION@@/$(PLUGIN_PROFTPD_VERSION)/g' |\
		sed -e 's/@@PLUGIN_SVN_VERSION@@/$(PLUGIN_SVN_VERSION)/g' |\
		sed -e 's/@@PLUGIN_FRS_VERSION@@/$(PLUGIN_FRS_VERSION)/g' |\
                sed -e 's/@@PLUGIN_REFERENCEALIAS_CORE_VERSION@@/$(PLUGIN_REFERENCEALIAS_CORE_VERSION)/g' |\
                sed -e 's/@@PLUGIN_REFERENCEALIAS_TRACKER_VERSION@@/$(PLUGIN_REFERENCEALIAS_TRACKER_VERSION)/g' |\
                sed -e 's/@@PLUGIN_REFERENCEALIAS_MEDIAWIKI_VERSION@@/$(PLUGIN_REFERENCEALIAS_MEDIAWIKI_VERSION)/g' |\
                sed -e 's/@@PLUGIN_REFERENCEALIAS_SVN_VERSION@@/$(PLUGIN_REFERENCEALIAS_SVN_VERSION)/g' |\
                sed -e 's/@@PLUGIN_REFERENCEALIAS_GIT_VERSION@@/$(PLUGIN_REFERENCEALIAS_GIT_VERSION)/g' |\
		sed -e 's/@@THEME_TULEAP_VERSION@@/$(THEME_TULEAP_VERSION)/g' |\
		sed -e 's/@@THEME_FLAMINGPARROT_VERSION@@/$(THEME_FLAMINGPARROT_VERSION)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/$(SPEC_FILE)
	rpmbuild -bs $(RPM_TMP)/SPECS/$(SPEC_FILE)

custom_rpm: $(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-customization-default-$(version)-$(RELEASE)$(DIST).noarch.rpm

$(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-customization-default-$(version)-$(RELEASE)$(DIST).noarch.rpm: $(RPM_TMP)/SRPMS/$(PKG_NAME)-customization-default-$(version)-$(RELEASE)$(DIST).src.rpm
	rpmbuild --rebuild $<

$(RPM_TMP)/SRPMS/$(PKG_NAME)-customization-default-$(version)-$(RELEASE)$(DIST).src.rpm: $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz
	cat $(CUSTOM_SPEC_FILE) |\
		sed -e 's/@@PLATFORM@@/default/' |\
		sed -e 's/@@SYS_DEFAULT_DOMAIN@@/tuleap.net/' |\
		sed -e 's/@@SYS_HTTPS_HOST@@/tuleap.net/' |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/codendi-customization.spec
	cp *_ParametersLocal.dtd $(RPM_TMP)/SOURCES/
	cp $(BASE_DIR)/src/www/themes/common/images/organization_logo.png $(RPM_TMP)/SOURCES/organization_logo.png
	rpmbuild -bs $(RPM_TMP)/SPECS/codendi-customization.spec

rpm-all-deps: $(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-all-deps-$(version)-$(RELEASE)$(DIST).noarch.rpm

$(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-all-deps-$(version)-$(RELEASE)$(DIST).noarch.rpm: $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-deps-$(version)-$(RELEASE)$(DIST).src.rpm
	rpmbuild --rebuild $<

$(RPM_TMP)/SRPMS/$(PKG_NAME)-all-deps-$(version)-$(RELEASE)$(DIST).src.rpm: codendi-all-deps.spec
	cat $< |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/$<
	cp README.all-deps $(RPM_TMP)/SOURCES/
	rpmbuild -bs $(RPM_TMP)/SPECS/$<

tuleap-all: $(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).noarch.rpm

$(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).noarch.rpm: $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).src.rpm
	rpmbuild --rebuild $<

$(RPM_TMP)/SRPMS/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).src.rpm:	tuleap-all.spec
	cat $< |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/$<
	cp README.all $(RPM_TMP)/SOURCES/
	rpmbuild -bs $(RPM_TMP)/SPECS/$<

clean:
	-rm -Rf $(RPM_TMP)/*
	@[ ! -d $(RPM_TMP) ] || rmdir $(RPM_TMP)
	-rm -Rf ~/.rpmmacros
