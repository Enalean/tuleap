RPM_TMP=$(HOME)/rpmbuild
BASE_DIR=$(shell cd ../..; pwd)
SHELL=/usr/bin/env bash -euo pipefail -c

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

ifeq ($(strip $(OS)),centos7)
SPEC_FILE=tuleap.rhel7.spec
DIST=.el7
SRPMS=$(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm
RPMBUILD += --define "%dist $(DIST)"
else ifeq ($(strip $(OS)),el9)
SPEC_FILE=tuleap.rhel7.spec
DIST=.el9
SRPMS=$(RPM_TMP)/SRPMS/$(PKG_NAME)-$(version)-$(RELEASE)$(DIST).src.rpm
RPMBUILD += --define "%dist $(DIST)"
else
$(error OS version not supported)
endif

.PHONY: all clean rpmprep rpm tarball node composer srpms dist

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
	     CYPRESS_INSTALL_BINARY=0 pnpm install --frozen-lockfile &&\
	     pnpm run build

composer:
	$(MAKE) -C $(BASE_DIR) composer preload MODE=Prod

$(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version).tar.gz: rpmprep node composer mo generate-templates
	[ -h $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(PKG_NAME)-$(version)
	rm -rf $(RPM_TMP)/nix
	cp -f `HOME=$(RPM_TMP)/nix nix-build --no-out-link $(BASE_DIR)/tools/rpm/tuleap-source-tarball.nix` $@

##
## Build RPM
##

rpm: $(RPM_TMP)/SPECS/$(SPEC_FILE)
	$(RPMBUILD) -bb $(RPM_TMP)/SPECS/$(SPEC_FILE)

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

clean:
	-rm -Rf $(RPM_TMP)/*
	@[ ! -d $(RPM_TMP) ] || rmdir $(RPM_TMP)
	-rm -Rf ~/.rpmmacros
