TARGETS=cvs.codendi cvsgraph geshi highlight htmlpurifier jpgraph.codendi mailman.codendi munin.codendi phpmyadmin.codendi php-pear-HTTP php-pear-HTTP-Header php-pear-HTTP-Download php-pecl-apc php-zendframework subversion viewvc.codendi
RPM_TMP=$(shell cd ..; pwd)

PKG_NAME=codendi_st

default: full

rpmprep:
	echo "%_topdir $(RPM_TMP)" > ~/.rpmmacros
	echo '%_tmppath %{_topdir}/TMP' >> ~/.rpmmacros
	echo '%_buildroot %{_tmppath}/%{name}-root' >> ~/.rpmmacros
	echo '%_sysconfdir /etc' >> ~/.rpmmacros

%: %.spec
	rpmbuild -bb $<
	# Keep a trace of the build package
	touch $@

all: $(TARGETS)

full: rpmprep all

clean-placeholder:
	rm -f $(TARGETS)

# Miss sources...
todo: codendi-eclipse codendi-jri subversion

# No longer relevant
old: php-oci8.codendi
