PKG_NAME=tuleap
BUILDHOST=
RSYNCOPTIONS=--delete
RSYNCOPTIONS=

fullbuild:
	@echo "choose on target of all, dependancies, gettestfromff, synctobuildhost"

dependancies:
	make -C rpm/SPECS PKG_NAME=$(PKG_NAME)

%:
	make -C codendi_tools/rpm $@ PKG_NAME=$(PKG_NAME)

gettestfromff:
	svn copy svn://scm.fusionforge.org/svnroot/fusionforge/trunk/tests .

synctobuildhost:
	[ -z "$(BUILDHOST)" ] || rsync -a $(RSYNCOPTIONS) ./ root@$(BUILDHOST):/root/tuleap/

remotebuild: synctobuildhost
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "cd /root/tuleap/ ; yum -y install make ; make all dist"

remotebuilddeps: synctobuildhost
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "chown -R root.root /root/tuleap/rpm"
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make dependancies"
