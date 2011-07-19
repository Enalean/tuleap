<?xml version="1.0" encoding="utf-8"?><xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

<!--
//
// Copyright (c) Xerox Corporation, Codendi 2007-2008.
// This file is licensed under the GNU General Public License version 2. See the file COPYING. 
//
//
-->

<xsl:template name="book.titlepage.recto">
  <!-- CX-SBT: Add the logo + Version/Date -->
  <div align="center">
    <p><img src="../../icons/logo.png"/></p>
  <xsl:choose>
    <xsl:when test="bookinfo/title">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/title"/>
    </xsl:when>
    <xsl:when test="title">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="title"/>
    </xsl:when>
  </xsl:choose>

  <xsl:choose>
    <xsl:when test="bookinfo/subtitle">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/subtitle"/>
    </xsl:when>
    <xsl:when test="subtitle">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="subtitle"/>
    </xsl:when>
  </xsl:choose>

  <p>Version <xsl:call-template name="get.last.version.revision"/></p>
  <p style="font-size: small"><xsl:call-template name="get.last.date.revision"/></p>
  </div>

<!--  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/revhistory"/> -->
<!-- <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/authorgroup"/> -->
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/legalnotice"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/copyright"/>
</xsl:template>

</xsl:stylesheet>
