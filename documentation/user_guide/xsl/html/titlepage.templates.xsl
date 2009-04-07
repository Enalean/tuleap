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
    <img src="../../icons/codendi.png"/><br/><br/>
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
  <br/><br/>
  <p>Version <xsl:call-template name="get.last.version.revision"/>
  <br/><font size="-2"><xsl:call-template name="get.last.date.revision"/></font>
  </p>
  <hr/></div>

<!--  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/revhistory"/> -->
<!-- <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/authorgroup"/> -->
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/legalnotice"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/copyright"/>
</xsl:template>

</xsl:stylesheet>
