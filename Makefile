PKG_NAME=tuleap

default: clean all

dependancies:
	make -C rpm/SPECS PKG_NAME=$(PKG_NAME)

%:
	make -C codendi_tools/rpm $@ PKG_NAME=$(PKG_NAME)
