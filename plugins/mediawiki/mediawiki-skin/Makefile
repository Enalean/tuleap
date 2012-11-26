MWSKINPATH=/usr/share/mediawiki/skins

all: FusionForge.php

FusionForge.php: FusionForge.php.renamed
	@echo 'Building $@'
	patch -p0 -o $@ FusionForge.php.renamed FusionForge.php.patch

FusionForge.php.renamed: $(MWSKINPATH)
	sed \
	    -e 's/MonoBook/FusionForge/g' \
	    -e 's/Shared between FusionForge/Shared between MonoBook/' \
	    -e 's/FusionForgeTemplateToolboxEnd/MonoBookTemplateToolboxEnd/g' \
	    -e 's/Using monobook/Using fusionforge/' \
	    -e "s/skinname = 'monobook/skinname = 'fusionforge/" \
	    -e "s/stylename = 'monobook/stylename = 'fusionforge/" \
	    <$(MWSKINPATH)/MonoBook.php >$@
	

$(MWSKINPATH):
	echo 'You must install mediawiki to get reference files'

cleanphp:
	rm -f FusionForge.php.renamed FusionForge.php.patch2

patchphp: FusionForge.php.renamed
	-diff -uN FusionForge.php.renamed FusionForge.php > FusionForge.php.patch2
