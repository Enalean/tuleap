<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml"/>
  <xsl:template match="/">
    <records>
    <xsl:for-each select="//slim:record" xmlns:slim="http://www.loc.gov/MARC21/slim">
      <xsl:element name="record">
        <xsl:apply-templates/>
      </xsl:element>
    </xsl:for-each>
    </records>
  </xsl:template>

  <!-- borrowed from http://wiki.tei-c.org/index.php/Remove-Namespaces.xsl: -->
  <xsl:template match="*">
    <xsl:element name="{local-name()}">
      <xsl:apply-templates select="@*|node()"/>
    </xsl:element>
  </xsl:template>

  <xsl:template match="@*">
    <xsl:attribute name="{local-name()}">
      <xsl:value-of select="."/>
    </xsl:attribute>
  </xsl:template>

</xsl:stylesheet>
