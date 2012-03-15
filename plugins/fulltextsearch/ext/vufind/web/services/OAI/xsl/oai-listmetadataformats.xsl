<?xml version="1.0" encoding="UTF-8"?>
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
      <request verb="ListMetadataFormats">http://digital.library.villanova.edu/OAIServer.php</request>
      <ListMetadataFormats>
        <metadataFormat>
          <metadataPrefix>oai_dc</metadataPrefix>
          <schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>
          <metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/</metadataNamespace>
        </metadataFormat>
      </ListMetadataFormats>
    </OAI-PMH>
  </xsl:template>
  
</xsl:stylesheet>