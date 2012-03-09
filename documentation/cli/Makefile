all: html-en html-fr html-onechunk-en html-onechunk-fr

html-en:
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_en_US.xsl xml/en_US/Codendi_CLI.xml

html-fr:
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_fr_FR.xsl xml/fr_FR/Codendi_CLI.xml

html-onechunk-en:
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_onechunk_en_US.xsl xml/en_US/Codendi_CLI.xml

html-onechunk-fr:
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_onechunk_fr_FR.xsl xml/fr_FR/Codendi_CLI.xml
