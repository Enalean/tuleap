DOCBOOK_DTD=$(shell cd "$(CURDIR)/../lib/docbook-dtd/" && pwd)

all: html-en html-fr html-onechunk-en html-onechunk-fr

# This seems unavoidable on centos5 but not necessary on ubuntu
# maybe with more recent version of centos it will vanish
fix_docbook_dtd_path:
	perl -pi -e "s%file:/usr/local/docbook-dtd%$(DOCBOOK_DTD)%" xml/en_US/Codendi_CLI.xml
	perl -pi -e "s%file:/usr/local/docbook-dtd%$(DOCBOOK_DTD)%" xml/fr_FR/Codendi_CLI.xml

html-en: fix_docbook_dtd_path
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_en_US.xsl xml/en_US/Codendi_CLI.xml

html-fr: fix_docbook_dtd_path
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_fr_FR.xsl xml/fr_FR/Codendi_CLI.xml

html-onechunk-en: fix_docbook_dtd_path
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_onechunk_en_US.xsl xml/en_US/Codendi_CLI.xml

html-onechunk-fr: fix_docbook_dtd_path
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_onechunk_fr_FR.xsl xml/fr_FR/Codendi_CLI.xml
