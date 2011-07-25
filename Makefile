PKG_NAME=tuleap
BUILDHOST=
RSYNCOPTIONS=--delete
RSYNCOPTIONS=

fullbuild:
	@echo "choose on target of all, dependancies, gettestfromff, synctobuildhost remotebuild remotebuilddeps remotebuilddepssrc"

dependanciessrc: 
	make -C rpm/SPECS srpms

dependancies: 
	make -C rpm/SPECS

depdist: dependanciessrc dependancies 
	make -C rpm/SPECS dist

%:
	make -C codendi_tools/rpm $@ PKG_NAME=$(PKG_NAME)

gettestfromff:
	svn copy svn://scm.fusionforge.org/svnroot/fusionforge/trunk/tests .

synctobuildhost:
	[ -z "$(BUILDHOST)" ] || rsync -a $(RSYNCOPTIONS) ./ root@$(BUILDHOST):/root/tuleap/

remoteclean: synctobuildhost
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make -C rpm/SPECS clean"

remotebuild: synctobuildhost
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "cd /root/tuleap/ ; yum -y install make ; make all dist"

remotebuilddepdist: synctobuildhost
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "chown -R root.root /root/tuleap/rpm"
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make depdist"

remotebuilddeps: synctobuildhost
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "chown -R root.root /root/tuleap/rpm"
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make dependancies"

remotebuilddepssrc: synctobuildhost
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "chown -R root.root /root/tuleap/rpm"
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make dependanciessrc"

mockbuild:
	@echo "Setup builder user"
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "useradd -g mock builder ; cp -r ~/.ssh ~builder ; chown -R builder.mock ~builder/.ssh ; yum -y install mock "
	@echo "Transfer Source"
	[ -z "$(BUILDHOST)" ] || rsync -a $(RSYNCOPTIONS) ./rpm builder@$(BUILDHOST):
	@echo "Build Source package"
	[ -z "$(BUILDHOST)" ] || ssh builder@$(BUILDHOST) "make -C rpm/SPECS srpms"
	@echo "Mock Config"
	if [ -f centos-5-x86_64.cfg ] ; then [ -z "$(BUILDHOST)" ] || scp centos-5-x86_64.cfg root@$(BUILDHOST):/etc/mock/ ; fi
	#[ -z "$(BUILDHOST)" ] || ssh builder@$(BUILDHOST) "mock --debug -r centos-5-x86_64 init"
	@echo "Mock Build, be patient"
	[ -z "$(BUILDHOST)" ] || ssh builder@$(BUILDHOST) "cd rpm/SRPMS ; mock -r centos-5-x86_64 *.src.rpm"

