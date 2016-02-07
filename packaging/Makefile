PACKAGINGDIR := $(shell pwd)
SRCDIR := $(PACKAGINGDIR)/../
NAME := tuleap-realtime

RPMDEFINES := --define "_sourcedir $(PACKAGINGDIR)" \
              --define "_specdir $(PACKAGINGDIR)" \
              --define "_srcrpmdir $(PACKAGINGDIR)" \
              --define "_rpmdir $(PACKAGINGDIR)"

srpm: $(NAME).tar.gz
	rpmbuild $(RPMDEFINES) -bs $(NAME).spec

clean:
	@echo Clean $(NAME) in $(PACKAGINGDIR)
	@rm -rfv *~ noarch x86_64 *.src.rpm
	@rm -f $(NAME).tar.gz~

build: $(NAME).tar.gz
	make clean
	time rpmbuild $(RPMDEFINES) -bb $(NAME).spec

$(NAME).tar.gz:
	cd $(SRCDIR) && find . \( -path '*/packaging' -o -path '*/node_modules' -o -path '*/ssl' -o -path '*/.git' -o -name '.gitignore' \) -prune -o -print | cpio -o -H ustar --quiet | gzip > $(PACKAGINGDIR)/$(NAME).tar.gz
