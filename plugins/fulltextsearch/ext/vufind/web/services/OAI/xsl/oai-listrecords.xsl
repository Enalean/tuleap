<?xml version="1.0"?>
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
      <request verb="ListRecords">http://digital.library.villanova.edu/OAIServer.php</request>
      <ListRecords>
        <xsl:apply-templates match="doc"/>
      </ListRecords>
    </OAI-PMH>
  </xsl:template>

  <xsl:template match="doc">  
    <record>
      <header>
        <identifier><xsl:value-of select="concat($label, substring(parent::node()/@name, string-length('/db/DigitalLibrary/ ')), '/', node())"/></identifier>
        <datestamp><xsl:value-of select="php:function('getISODate', string(@modified))"/></datestamp>
        <setSpec><xsl:value-of select="concat('collection:', translate(substring(parent::node()/@name, string-length('/db/DigitalLibrary/ ')), '/', ':'))"/></setSpec>
      </header>
      <metadata>
        <xsl:copy-of select="php:function('getRecord', concat(substring(parent::node()/@name, string-length('/db/DigitalLibrary/ ')), '/', node()))"/>
      </metadata>      
    </record>
  </xsl:template>
  
</xsl:stylesheet>