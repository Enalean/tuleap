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
