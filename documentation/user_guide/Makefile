all: html-en html-fr

html-en:
	perl -pi -e 's%/usr/local/docbook-xsl/html/chunk.xsl%../../../lib/docbook-xsl/html/chunk.xsl%' xsl/htmlhelp/htmlhelp_en_US.xsl
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_en_US.xsl xml/en_US/User_Guide.xml

html-fr:
	perl -pi -e 's%/usr/local/docbook-xsl/html/chunk.xsl%../../../lib/docbook-xsl/html/chunk.xsl%' xsl/htmlhelp/htmlhelp_fr_FR.xsl
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_fr_FR.xsl xml/fr_FR/User_Guide.xml
