<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>
<xsl:template match="/rss" >
<xsl:for-each select="channel">
   <html xml:lang="{language}">
   <head>
        <title><xsl:value-of select="title" /></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
   </head>
   <body>
       <h1><a href="{link}"><xsl:value-of select="title" /></a></h1>
       <p><xsl:value-of select="description" /></p>
       <xsl:for-each select="item">
             <h2><a href="{link}" title="{description}"><xsl:value-of select="title" /></a></h2>
             <p><xsl:value-of select="description" /></p>
       </xsl:for-each>
   </body>
</html>
</xsl:for-each>
</xsl:template>
</xsl:stylesheet>
