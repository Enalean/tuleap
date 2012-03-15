<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                              xmlns:php="http://php.net/xsl"
                              xsl:extension-element-prefixes="php">

  <xsl:output method="xml" indent="yes"/>
  
  <xsl:template match="/">
    <OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
             xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
             xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
             http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">  
      <responseDate><xsl:value-of select="php:function('date', 'Y-m-d\TH:i:s\Z')"/></responseDate>
      <request verb="ListSets">http://digital.library.villanova.edu/OAIServer.php</request>
      <ListSets>
        <xsl:for-each select="//collection">
          <xsl:if test="position() != 1">
          <set>
            <setSpec><xsl:value-of select="translate(substring(@name, string-length('/db/DigitalLibrary/ ')), '/', ':')"/></setSpec>
            <setName><xsl:value-of select="php:function('basename', string(@name))"/></setName>
          </set>
          </xsl:if>
        </xsl:for-each>
      </ListSets>
    </OAI-PMH>
  </xsl:template>
 
</xsl:stylesheet>