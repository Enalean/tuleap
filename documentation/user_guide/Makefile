DOCBOOK_DTD=$(shell cd "$(CURDIR)/../lib/docbook-dtd/" && pwd)

all: html-en html-fr html-onepage-en html-onepage-fr

# This seems unavoidable on centos5 but not necessary on ubuntu
# maybe with more recent version of centos it will vanish
fix_docbook_dtd_path:
	perl -pi -e "s%file:/usr/local/docbook-dtd%$(DOCBOOK_DTD)%" xml/en_US/User_Guide.xml
	perl -pi -e "s%file:/usr/local/docbook-dtd%$(DOCBOOK_DTD)%" xml/fr_FR/User_Guide.xml

html-en: fix_docbook_dtd_path
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_en_US.xsl xml/en_US/User_Guide.xml

html-fr: fix_docbook_dtd_path
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_fr_FR.xsl xml/fr_FR/User_Guide.xml

html-onepage-en: fix_docbook_dtd_path
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_onechunk_en_US.xsl xml/en_US/User_Guide.xml

html-onepage-fr: fix_docbook_dtd_path
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_onechunk_fr_FR.xsl xml/fr_FR/User_Guide.xml

test:
	@echo $(DOCBOOK_DTD)
