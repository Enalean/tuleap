PKG_NAME=tuleap

default: all

dependancies:
	make -C rpm/SPECS PKG_NAME=$(PKG_NAME)

%:
	make -C codendi_tools/rpm $@ PKG_NAME=$(PKG_NAME)

gettestfromff:
	svn copy svn://scm.fusionforge.org/svnroot/fusionforge/trunk/tests .
	
