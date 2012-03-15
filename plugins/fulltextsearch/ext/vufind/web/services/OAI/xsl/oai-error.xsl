<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                              xmlns:dc="http://purl.org/dc/elements/1.1/"
                              xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                              xmlns:mets="http://www.loc.gov/METS/"
                              xmlns:php="http://php.net/xsl"
                              xsl:extension-element-prefixes="php">

  <xsl:output method="xml" indent="yes"/>
  
  <xsl:template match="/">
    <OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
             xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
             xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
             http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">  
      <responseDate><xsl:value-of select="php:function('date', 'Y-m-d\TH:i:s\Z')"/></responseDate>
      <request>http://digital.library.villanova.edu/OAIServer.php</request>
      <xsl:copy-of select="error"/>
    </OAI-PMH>
  </xsl:template>
  
</xsl:stylesheet>