#! /usr/bin/make -f

list:
	@echo ======================================================================================
	@echo '=                    Available target are listed below                               ='
	@echo '=                         ~ build rpm packages ~                                     ='
	@echo ======================================================================================
	@cat Makefile | grep '^.*:.*#$$' | sed 's/^\(.*:\).*#\(.*\)#$$/\1		\2/'

###Checkout Source
checkout: 	# [ First ] Checkout sources from Xerox #
	-mkdir ./codex 2> /dev/null
	svn checkout https://partners.xrce.xerox.com/svnroot/codex/support/CX_3_6_SUP codex

###Do it all
all: checkout codendi allplugins dist          # Checkout source, Build and Install codendi and plugins #
cleanall: clean cleanplugins   # clean codendi and plugins #

###Codendi
codendi: clean build  install  alldeps      # Build and install codendi and plugins#

###Dependencies
alldeps: htmlpurifier jpgraph 	# Install CodeX Post Dependencies	#
	echo "Installing JPGraph RPM for CodeX...."	
	su -c 'rpm -Uvh /tmp/codendi-build/RPMS/noarch/jpgraph-*.noarch.rpm'
	echo "Installing htmlpurifier RPM for CodeX...."
	su -c 'rpm -Uvh /tmp/codendi-build/RPMS/noarch/htmlpurifier-*.noarch.rpm'

# Build Deps
htmlpurifier: rpmprep	
	cp codex/rpm/specs/htmlpurifier.spec /tmp/codendi-build/SPEC/.
	cp codex/rpm/sources/htmlpurifier-3.1.1.tar.gz /tmp/codendi-build/SOURCE/.
	rpmbuild -ba --rcfile rpmrc /tmp/codendi-build/SPEC/htmlpurifier.spec 

jpgraph: rpmprep	
	cp codex/rpm/specs/jpgraph.codex.spec /tmp/codendi-build/SPEC/.
	cp codex/rpm/sources/jpgraph-* /tmp/codendi-build/SOURCE/.
	rpmbuild -ba --rcfile rpmrc /tmp/codendi-build/SPEC/jpgraph.codex.spec


###Plugins 
allplugins: 		# Install All Plugins #
	@echo "Install All Plugins OK"
	
cleanplugins: 			# Clean All Plugins #
	@echo "Clean All Plugins OK"


RPM_TMP=/tmp/codendi-build

###build a custom rpmmacro file and rpmrc to point to it for rpmbuild
rpmrc:
	echo 'include: /usr/lib/rpm/rpmrc' > ./rpmrc

rpmmacro:
	echo '$(shell rpmbuild --showrc | grep '^macrofiles'):./rpmmacros' >> ./rpmrc
	echo '%_topdir $(RPM_TMP)' > ./rpmmacros
	echo '%_rpmtopdir %{_topdir}' >> ./rpmmacros
	echo '%_builddir %{_rpmtopdir}/BUILD' >> ./rpmmacros
	echo '%_rpmdir %{_rpmtopdir}/RPMS' >> ./rpmmacros
	echo '%_sourcedir %{_rpmtopdir}/SOURCE' >> ./rpmmacros
	echo '%_specdir %{_rpmtopdir}/SPEC' >> ./rpmmacros
	echo '%_srcrpmdir %{_rpmtopdir}/SRPMS' >> ./rpmmacros
	echo '%_tmppath %{_rpmtopdir}/TMP' >> ./rpmmacros
	echo '%_buildroot %{_tmppath}/%{name}-root' >> ./rpmmacros

rpmprep: rpmrc rpmmacro		
	-mkdir -p $(RPM_TMP)/BUILD $(RPM_TMP)/RPMS $(RPM_TMP)/SOURCE $(RPM_TMP)/SPEC $(RPM_TMP)/SRPMS $(RPM_TMP)/TMP 2> /dev/null

dist: rpmprep
	-mkdir ./dist 2> /dev/null
	mv ./*.bz2 $(RPM_TMP)/RPMS/noarch/*.rpm ./dist

#
### codendi
#
target clean build : override version=$(shell grep '^Version:' codex/codex.spec | sed 's/.*:\s*\(.*\)/\1/')

clean:		# cleanall files of codendi build                         #
	@rm -rf codendi-$(version).tar.bz2 codendi-$(version)
	@echo clean Done

###Add conf variable
addconf: 
	sh tools/addconf.sh

### build CodeX
build: rpmprep addconf		# Build rpm codendi packages                               #
	cd codex ;find . -type f | grep -v '/.svn/' | grep -v plugins |grep -v documentation | grep -v rpm | cpio -pdumvB ../codendi-$(version)
	cp tools/codex.spec codendi-$(version)/.
	cp tools/sed.sh codendi-$(version)/.
	tar cvjf codendi-$(version).tar.bz2 codendi-$(version)
	rpmbuild -ta --rcfile rpmrc codendi-$(version).tar.bz2

install: 		# Install Codendi rpm (if you have executed the build) 		#
	su -c 'rpm -ivh /tmp/codendi-build/RPMS/noarch/codendi-3.6-1.noarch.rpm ; rm -f /etc/httpd/conf.d/ssl.conf'
	echo "use codex ; delete from plugin where id=8;" > /tmp/delete_im.sql
	mysql -u codexadm -p < /tmp/delete_im.sql
	rm /tmp/delete_im.sql
	su -c 'sed -i "s|LoadModule|#LoadModule|g" /etc/httpd/conf.d/proxy_ajp.conf ; sed -i "s|LoadModule|#LoadModule|g" /etc/httpd/conf.d/subversion.conf ; /etc/init.d/mysqld start; /etc/init.d/httpd start'
