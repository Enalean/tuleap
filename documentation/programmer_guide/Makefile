DOCBOOK_DTD=$(shell cd "$(CURDIR)/../lib/docbook-dtd/" && pwd)

all: html-en

# This seems unavoidable on centos5 but not necessary on ubuntu
# maybe with more recent version of centos it will vanish
fix_docbook_dtd_path:
	perl -pi -e "s%file:/usr/local/docbook-dtd%$(DOCBOOK_DTD)%" xml/en_US/Codendi_Programmer_Guide.xml

html-en: fix_docbook_dtd_path
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp.xsl xml/en_US/Codendi_Programmer_Guide.xml
