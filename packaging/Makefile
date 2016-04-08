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

VERSION := $(shell cat ../package.json | grep version | cut -d: -f 2 | tr -d ',' | tr -d '"' | tr -d " ")
PACKAGE_NAME_VERSION := $(NAME)-$(VERSION)
RPMDEFINES := --define "_topdir $(RPM_TMP)"

srpm: $(NAME).tar.gz
	rpmbuild $(RPMDEFINES) -bs $(NAME).spec

clean:
	@echo Clean $(NAME) in $(PACKAGINGDIR)
	@rm -rfv *~ noarch x86_64 *.src.rpm
	@rm -f $(NAME).tar.gz

build:
	@[ -n "$(GID)" -a -n "$(UID)" ] || (echo "*** ERROR: UID or GID are missing" && false)
	@[ -n "$(RELEASE)" ] || (echo "*** ERROR: RELEASE is missing" && false)
	useradd -d /build -m builder_user
	su --login --command "make -C /realtime/packaging rpm RELEASE=$(RELEASE)" builder_user
	install -o $(UID) -g $(GID) -m 0644 /build/rpmbuild/RPMS/noarch/$(PACKAGE_NAME_VERSION)-$(RELEASE).noarch.rpm $(PACKAGINGDIR)

rpm: clean $(RPM_TMP)/SOURCES/$(NAME).tar.gz $(RPM_TMP)/SOURCES/$(NAME).conf $(RPM_TMP)/SOURCES/$(NAME).service $(RPM_TMP)/SOURCES/logrotate.conf
	cat $(NAME).spec | sed -e 's/@@VERSION@@/$(VERSION)/' -e 's/@@RELEASE@@/$(RELEASE)/' > $(RPM_TMP)/SPECS/$(NAME).spec
	time rpmbuild $(RPMDEFINES) -bb $(RPM_TMP)/SPECS/$(NAME).spec

$(RPM_TMP)/SOURCES/$(NAME).tar.gz: $(RPM_TMP)
	cd $(SRCDIR) && find . \( -path '*/packaging' -o -path '*/node_modules' -o -path '*/ssl' -o -path '*/.git' -o -name '.gitignore' \) -prune -o -print | cpio -o -H ustar --quiet | gzip > $(RPM_TMP)/SOURCES/$(NAME).tar.gz

$(RPM_TMP)/SOURCES/$(NAME).conf:
	cp $(NAME).conf $@

$(RPM_TMP)/SOURCES/$(NAME).service:
	cp $(NAME).service $@

$(RPM_TMP)/SOURCES/logrotate.conf:
	cp logrotate.conf $@

$(RPM_TMP):
	@[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP
