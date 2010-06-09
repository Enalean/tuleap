ifeq ($(strip $(RPM_TMP)),)
	RPM_TMP=~/rpmbuild
endif

#BUILDRESULT=${PWD}/fusionforge_repo
BASE_DIR=$(shell cd ../..; pwd)
version=$(shell LANG=C cat $(BASE_DIR)/ST-VERSION)

forgename=codendi_st

rpmprep:
	mkdir -p $(RPM_TMP)
	cd $(RPM_TMP) && mkdir BUILD RPMS SOURCES SPECS SRPMS TMP
	echo "%_topdir $(RPM_TMP)" > ~/.rpmmacros
	echo '%_tmppath %{_topdir}/TMP' >> ~/.rpmmacros
	echo '%_buildroot %{_tmppath}/%{name}-root' >> ~/.rpmmacros
	echo '%_sysconfdir /etc' >> ~/.rpmmacros

tarball:
	cd $(BASE_DIR) && find . -type f -or -type l | grep -v '/.svn/' | cpio -pdumB --quiet $(RPM_TMP)/SOURCES/$(forgename)-$(version)
	cd $(RPM_TMP)/SOURCES && tar czf $(forgename)-$(version).tar.gz $(forgename)-$(version)

codendi: rpmprep tarball
	sed -e 's/@@VERSION@@/$(version)/g' < $(forgename).spec > $(RPM_TMP)/SPECS/$(forgename).spec
	rpmbuild -bb $(RPM_TMP)/SPECS/$(forgename).spec

custom_codendi: tarball
	cat codendi_st_customization_tmpl.spec |\
		sed -e 's/@@PLATFORM@@/codendi/' |\
		sed -e 's/@@SYS_DEFAULT_DOMAIN@@/codendi.org/' |\
		sed -e 's/@@SYS_HTTPS_HOST@@/codendi.org/' |\
		sed -e 's/@@VERSION@@/$(version)/g' \
		> $(RPM_TMP)/SPECS/codendi_st_customization.spec
	cp *_ParametersLocal.dtd $(RPM_TMP)/SOURCES
	cp $(BASE_DIR)/src/www/themes/common/images/organization_logo.png $(RPM_TMP)/SOURCES/codendi_organization_logo.png

	rpmbuild -bb $(RPM_TMP)/SPECS/codendi_st_customization.spec

clean:
	-rm -Rf $(RPM_TMP)/*
	-rm -Rf ~/.rpmmacros

all: clean codendi custom_codendi
