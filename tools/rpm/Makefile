RPM_TMP=$(HOME)/rpmbuild
BASE_DIR=$(shell cd ../..; pwd)
SHELL=/bin/bash -euo pipefail

version=$(shell LANG=C cat $(BASE_DIR)/VERSION)

ifdef GIT_BRANCH
TAG_RELEASE = $(subst -, ,$(GIT_BRANCH))
RELEASE = $(word 2, $(TAG_RELEASE))
endif

ifeq ($(strip $(RELEASE)),)
RELEASE=1
endif

PKG_NAME=tuleap

RPMBUILD = rpmbuild --define "%_topdir $(RPM_TMP)" \
	--define "%_tmppath %{_topdir}/TMP" \
	--define "%_buildroot %{_tmppath}/%{name}-root" \
	--define "%_sysconfdir /etc" \

ifneq ("$(wildcard $(BASE_DIR)/ENTERPRISE_BUILD)","")
	RPMBUILD += --with enterprise
endif

ifeq ($(strip $(EXPERIMENTAL_BUILD)),1)
	RPMBUILD += --with experimental
endif

ifeq ($(strip $(OS)),rhel6)
DIST=
SPEC_FILE=tuleap.rhel6.spec
SRPMS=$(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm \
	$(RPM_TMP)/SRPMS/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).src.rpm
else
ifeq ($(strip $(OS)),rhel7)
SPEC_FILE=tuleap.rhel7.spec
DIST=.el7
SRPMS=$(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm
RPMBUILD += --define "%dist $(DIST)"
else
$(error OS version not supported)
endif
endif

.PHONY: all clean rpmprep rpm tuleap-all tarball node composer srpms dist

.PRECIOUS: $(RPM_TMP) $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz

.SECONDARY:

default: all

all: srpms
	rpmbuild --rebuild $(SRPMS)

##
## Setup environment
##

rpmprep: $(RPM_TMP)

$(RPM_TMP):
	[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP

##
## Build tarball
##

tarball: rpmprep $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz

generate-templates:
	cd $(BASE_DIR) &&\
		$(MAKE) generate-templates

mo:
	cd $(BASE_DIR) &&\
		tools/utils/generate-mo.sh $(BASE_DIR)

node:
	cd $(BASE_DIR) &&\
	     npm install --no-audit &&\
	     npm run build

composer:
	$(MAKE) -C $(BASE_DIR) composer MODE=Prod

$(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz: rpmprep node composer mo generate-templates
	[ -h $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version)
	cd $(RPM_TMP)/SOURCES && \
		find $(PKG_NAME)-$(version)/ \(\
			-path '*/.svn' -o\
			-name '*.less' -o\
			-name '*.scss' -o\
			-name '.scss-lint.yml' -o\
			-name 'package-lock.json' -o\
			-name ".git" -o\
			-name ".gitignore" -o\
			-name ".gitmodules" -o\
			-name "node_modules" -o\
			-path "*/.DS_Store" \)\
			-prune -o -print | \
		cpio -o -H ustar --quiet |\
		gzip > $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz

##
## Build RPM
##

rpm: $(RPM_TMP)/SPECS/$(SPEC_FILE) $(RPM_TMP)/SPECS/tuleap-all.spec
	$(RPMBUILD) -bb $(RPM_TMP)/SPECS/$(SPEC_FILE)
	$(RPMBUILD) -bb $(RPM_TMP)/SPECS/tuleap-all.spec

srpms: $(SRPMS)

srpms-docker: $(SRPMS)

$(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).noarch.rpm: $(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm
	rpmbuild --rebuild $<

$(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm: $(SPEC_FILE) $(RPM_TMP)/SPECS/$(SPEC_FILE) $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz
	$(RPMBUILD) -bs $(RPM_TMP)/SPECS/$(SPEC_FILE)

$(RPM_TMP)/SPECS/$(SPEC_FILE): $(SPEC_FILE)
	cat $(SPEC_FILE) |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@RELEASE@@/$(RELEASE)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/$(SPEC_FILE)

$(RPM_TMP)/SPECS/tuleap-all.spec: tuleap-all.spec
	cat tuleap-all.spec |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/tuleap-all.spec

tuleap-all: $(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).noarch.rpm

$(RPM_TMP)/RPMS/noarch/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).noarch.rpm: $(RPM_TMP)/SRPMS/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).src.rpm
	rpmbuild --rebuild $<

$(RPM_TMP)/SRPMS/$(PKG_NAME)-all-$(version)-$(RELEASE)$(DIST).src.rpm:	tuleap-all.spec
	cat $< |\
		sed -e 's/@@VERSION@@/$(version)/g' |\
		sed -e 's/@@PKG_NAME@@/$(PKG_NAME)/g' \
		> $(RPM_TMP)/SPECS/$<
	$(RPMBUILD) -bs $(RPM_TMP)/SPECS/$<

clean:
	-rm -Rf $(RPM_TMP)/*
	@[ ! -d $(RPM_TMP) ] || rmdir $(RPM_TMP)
	-rm -Rf ~/.rpmmacros
