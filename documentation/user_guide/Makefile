all: html-en html-fr html-onepage-en html-onepage-fr

html-en:
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_en_US.xsl xml/en_US/User_Guide.xml

html-fr:
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_fr_FR.xsl xml/fr_FR/User_Guide.xml

html-onepage-en:
	xsltproc -o html/en_US/ xsl/htmlhelp/htmlhelp_onechunk_en_US.xsl xml/en_US/User_Guide.xml

html-onepage-fr:
	xsltproc -o html/fr_FR/ xsl/htmlhelp/htmlhelp_onechunk_fr_FR.xsl xml/fr_FR/User_Guide.xml
