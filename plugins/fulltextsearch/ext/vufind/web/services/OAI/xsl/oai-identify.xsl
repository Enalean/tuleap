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
      <request verb="Identify"><xsl:value-of select="$baseUrl"/></request>
      <Identify>
        <repositoryName><xsl:value-of select="$repoName"/></repositoryName>
        <baseURL><xsl:value-of select="$baseUrl"/></baseURL>
        <protocolVersion>2.0</protocolVersion>
        <adminEmail><xsl:value-of select="$email"/></adminEmail>
        <earliestDatestamp>2008-10-01T00:00:00Z</earliestDatestamp>
        <deletedRecord>no</deletedRecord>
        <granularity>YYYY-MM-DDThh:mm:ssZ</granularity>
        <description>
          <oai-identifier xmlns="http://www.openarchives.org/OAI/2.0/oai-identifier"
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                          xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai-identifier
                                              http://www.openarchives.org/OAI/2.0/oai-identifier.xsd">
            <scheme>oai</scheme>
            <repositoryIdentifier><xsl:value-of select="$identifier"/></repositoryIdentifier>
            <delimiter>:</delimiter>
          </oai-identifier>
        </description>
      </Identify>
    </OAI-PMH>
  </xsl:template>
  
</xsl:stylesheet>