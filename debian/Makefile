BASE_DIR=$(shell cd ..; pwd)

default: all

all: less deb

less:
	$(MAKE) -C .. less

deb:
	cd $(BASE_DIR) && dpkg-buildpackage

