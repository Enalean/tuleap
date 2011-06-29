PKG_NAME=tuleap
BUILDHOST=

default: all

dependancies:
	make -C rpm/SPECS PKG_NAME=$(PKG_NAME)

%:
	make -C codendi_tools/rpm $@ PKG_NAME=$(PKG_NAME)

gettestfromff:
	svn copy svn://scm.fusionforge.org/svnroot/fusionforge/trunk/tests .

synctobuildhost:
	[ -z "$(BUILDHOST)" ] || rsync -av ./ root@$(BUILDHOST):/root/tuleap/
	[ -z "$(BUILDHOST)" ] || ssh root@$(BUILDHOST) "cd /root/tuleap/ ; yum -y install make ; make"
	
