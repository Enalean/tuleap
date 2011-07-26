PKG_NAME=tuleap
BUILDHOST=
RSYNCOPTIONS=--delete
RSYNCOPTIONS=

help:
	@echo "Choose one target"
	@echo "	Available targets are: `grep '^[^	]*:' Makefile | grep -v %| cut -d: -f1 | xargs echo`"

dependanciessrc: 
	@echo "Create dependancies SRPMS"
	make -C rpm/SPECS srpms

dependancies: 
	@echo "Build depedancies RPMS"
	make -C rpm/SPECS

depdist: dependanciessrc dependancies  
	@echo "Build dependancies repository"
	make -C rpm/SPECS dist

%:
	@echo "Make codendi package target $@ with PKG_NAME=$(PKG_NAME)"
	make -C codendi_tools/rpm $@ PKG_NAME=$(PKG_NAME)

# Check if BUILDHOST is defined
checkbuildhost:
	@[ ! -z "$(BUILDHOST)" ] ||  (echo "You must set BUILDHOST with e.g make BUILDHOST=<hostname>" && false )

# Get test suite form fusionforge, essentially to make lxc vm at the time writing
gettestfromff:
	svn copy svn://scm.fusionforge.org/svnroot/fusionforge/trunk/tests .

# Build SRPMS with mock
mockbuild: dependanciessrc
	@echo "Mock Build, be patient"
	make -C rpm/SRPMS

# Copy current dir to BUILDHOST
synctobuildhost: checkbuildhost
	rsync -a $(RSYNCOPTIONS) ./ root@$(BUILDHOST):/root/tuleap/

# The next target are used for remote operations related to tuleap build
remoteclean: synctobuildhost
	ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make -C rpm/SPECS clean"

remotebuild: synctobuildhost
	ssh root@$(BUILDHOST) "cd /root/tuleap/ ; yum -y install make ; make all dist"

remotebuilddepdist: synctobuildhost
	ssh root@$(BUILDHOST) "chown -R root.root /root/tuleap/rpm"
	ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make depdist"

remotebuilddeps: synctobuildhost
	ssh root@$(BUILDHOST) "chown -R root.root /root/tuleap/rpm"
	ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make dependancies"

remotebuilddepssrc: synctobuildhost
	ssh root@$(BUILDHOST) "chown -R root.root /root/tuleap/rpm"
	ssh root@$(BUILDHOST) "cd /root/tuleap/ ; make dependanciessrc"

# mock related operations
# prepare remote BUILDHOST to be able to use mock
# mock require a non root user in group mock 
# the builder user is created, ssh with key made available, mock installed
# If you need to define specific mirrors just create a centos-5-x86_64.cfg replacement file
remotemockprepare: checkbuildhost
	@echo "Setup builder user"
	ssh root@$(BUILDHOST) "useradd -g mock builder ; cp -r ~/.ssh ~builder ; chown -R builder.mock ~builder/.ssh ; yum -y install mock "
	@echo "Mock Config"
	[ ! -f centos-5-x86_64.cfg ] || scp centos-5-x86_64.cfg root@$(BUILDHOST):/etc/mock/ 
	
remotemockbuild: remotemockprepare
	@echo "Transfer Source"
	rsync -a $(RSYNCOPTIONS) ./rpm builder@$(BUILDHOST):
	scp Makefile builder@$(BUILDHOST):
	@echo "Call mockbuild on $(BUILDHOST)"
	ssh builder@$(BUILDHOST) "make mockbuild"
	@echo "Get result"
	rsync -a builder@$(BUILDHOST):yum/ yum/

