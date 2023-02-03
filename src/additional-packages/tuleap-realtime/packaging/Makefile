RPM_TMP=$(HOME)/rpmbuild
PACKAGINGDIR := $(shell pwd)
SRCDIR := $(PACKAGINGDIR)/../
NAME := tuleap-realtime

# This meant to avoid having git in the docker container
# RELEASE is computed by launcher (for instance jenkins) where git is installed
# and the passed as an absolute value
ifeq ($(RELEASE),)
	RELEASE=1
endif

ifeq ($(OS),)
	DIST=
endif
ifeq ($(OS),centos:7)
	DIST=.el7
endif

VERSION := $(shell cat ../package.json | grep version | cut -d: -f 2 | tr -d ',' | tr -d '"' | tr -d " ")
PACKAGE_NAME_VERSION := $(NAME)-$(VERSION)
RPMDEFINES := --define "_topdir $(RPM_TMP)" --define "dist $(DIST)"

srpm: $(NAME).tar.gz
	rpmbuild $(RPMDEFINES) -bs $(NAME).spec

clean:
	@echo Clean $(NAME) in $(PACKAGINGDIR)
	@rm -rfv *~ noarch x86_64 *.src.rpm
	@rm -f $(NAME).tar.gz

build:
	@[ -n "$(GID)" -a -n "$(UID)" ] || (echo "*** ERROR: UID or GID are missing" && false)
	@[ -n "$(RELEASE)" ] || (echo "*** ERROR: RELEASE is missing" && false)
	@[ -n "$(OS)" ] || (echo "*** ERROR: OS is missing" && false)
	useradd -d /build -m builder_user
	su --login --command "make -C /realtime/packaging rpm RELEASE=$(RELEASE) OS=$(OS)" builder_user
	find /build/rpmbuild/RPMS/ -name '*.rpm' -exec install -o $(UID) -g $(GID) -m 0644 {} /output \;

rpm: clean $(RPM_TMP)/SOURCES/$(NAME).tar.gz $(RPM_TMP)/SOURCES/$(NAME).systemd-service
	cat $(NAME).spec | sed -e 's/@@VERSION@@/$(VERSION)/' -e 's/@@RELEASE@@/$(RELEASE)/' > $(RPM_TMP)/SPECS/$(NAME).spec
	time rpmbuild $(RPMDEFINES) -bb $(RPM_TMP)/SPECS/$(NAME).spec

$(RPM_TMP)/SOURCES/$(NAME).tar.gz: $(RPM_TMP)
	cd $(SRCDIR) && tar -zcvf $(RPM_TMP)/SOURCES/$(NAME).tar.gz ./config/*.json ./dist

$(RPM_TMP)/SOURCES/$(NAME).systemd-service:
	cp $(NAME).systemd-service $@

$(RPM_TMP):
	@[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP
