<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version='1.0'>

<!--
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id: 
//
//	Originally written by Stephane Bouhet 2002, CodeX Team, Xerox
//
-->

<!-- ==================================================================== -->


<!-- CX-SBT: Revision history table + separate file -->
<xsl:template match="revhistory" mode="titlepage.mode">

  <xsl:variable name="numcols">
    <xsl:choose>
      <xsl:when test="//authorinitials">4</xsl:when>
      <xsl:otherwise>3</xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <!-- CX-SBT: RevHistory filename : set to revhistory.html -->
  <xsl:variable name="filename">
    <xsl:call-template name="make-relative-filename">
      <xsl:with-param name="base.dir" select="$base.dir"/>
      <xsl:with-param name="base.name" select="concat('revhistory','',$html.ext)"/>
    </xsl:call-template>
  </xsl:variable>

  <xsl:variable name="title">
    <xsl:call-template name="gentext">
      <xsl:with-param name="key" select="'RevHistory'"/>
    </xsl:call-template>
  </xsl:variable>

  <p>
  <a href="{$filename}"><b>
    <xsl:value-of select="$title"/>
  </b></a>
  </p>

  <xsl:call-template name="write.chunk">
    <xsl:with-param name="filename" select="$filename"/>
    <xsl:with-param name="quiet" select="$chunk.quietly"/>
    <xsl:with-param name="content">
      <html>
        <head>
          <LINK type="text/css" href="current_css.php" rel="stylesheet"/>  
          <title><xsl:value-of select="$title"/></title>
        </head>
        <body>
          <xsl:call-template name="body.attributes"/>
          <div class="{name(.)}">
            <p><b>
            <xsl:call-template name="gentext">
              <xsl:with-param name="key" select="'RevHistory'"/>
            </xsl:call-template>
            </b></p>
            <table border="1" width="100%" summary="Revision history" cellspacing="0" cellpadding="1" BORDERCOLORLIGHT="#CCCCCC" BORDERCOLORdark="#CCCCCC">
              <tr bgcolor="#EEEEEE">
                <th align="center" valign="top">
                  <b>Version
                  </b>
                </th>
                <th align="center" valign="top">
                  <b>Date
                  </b>
                </th>
                <th align="center" valign="top">
                  <b>Description
                  </b>
                </th>
                <th align="center" valign="top">
                  <b>Author
                  </b>
                </th>
              </tr>
              <xsl:apply-templates mode="titlepage.mode">
                <xsl:with-param name="numcols" select="$numcols"/>
              </xsl:apply-templates>
            </table>
          </div>
        </body>
      </html>
    </xsl:with-param>
  </xsl:call-template>

</xsl:template>

<!-- CX-SBT: Revision history rows -->
<xsl:template match="revhistory/revision" mode="titlepage.mode">
  <xsl:param name="numcols" select="'3'"/>
  <xsl:variable name="revnumber" select=".//revnumber"/>
  <xsl:variable name="revdate"   select=".//date"/>
  <xsl:variable name="revauthor" select=".//authorinitials"/>
  <xsl:variable name="revremark" select=".//revremark|.//revdescription"/>
  <tr>
    <td align="left">
      <xsl:if test="$revnumber">
        <xsl:apply-templates select="$revnumber[1]" mode="titlepage.mode"/>
      </xsl:if>
    </td>
    <td align="left">
      <xsl:apply-templates select="$revdate[1]" mode="titlepage.mode"/>
    </td>
    <td align="left">
      <xsl:if test="$revremark">
        <xsl:apply-templates select="$revremark[1]" mode="titlepage.mode"/>
      </xsl:if>
    </td>
    <xsl:choose>
      <xsl:when test="$revauthor">
        <td align="left">
          <xsl:apply-templates select="$revauthor[1]" mode="titlepage.mode"/>
        </td>
      </xsl:when>
      <xsl:when test="$numcols &gt; 2">
        <td>&#160;</td>
      </xsl:when>
      <xsl:otherwise></xsl:otherwise>
    </xsl:choose>
  </tr>
</xsl:template>

<!-- ==================================================================== -->

<xsl:template match="legalnotice " mode="titlepage.mode">
  <xsl:variable name="id"><xsl:call-template name="object.id"/></xsl:variable>
  <xsl:choose>
    <xsl:when test="$generate.legalnotice.link != 0">
      <!-- CX-SBT: Legalnotice filename : set to legalnotice.html -->
      <xsl:variable name="filename">
        <xsl:call-template name="make-relative-filename">
          <xsl:with-param name="base.dir" select="$base.dir"/>
          <xsl:with-param name="base.name" select="concat('legalnotice','',$html.ext)"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:variable name="title">
        <xsl:apply-templates select="." mode="title.markup"/>
      </xsl:variable>

      <a href="{$filename}"><b>
        <xsl:copy-of select="$title"/>
      </b></a>

      <xsl:call-template name="write.chunk">
        <xsl:with-param name="filename" select="$filename"/>
        <xsl:with-param name="quiet" select="$chunk.quietly"/>
        <xsl:with-param name="content">
          <html>
            <head>
              <LINK type="text/css" href="current_css.php" rel="stylesheet"/>  
              <title><xsl:value-of select="$title"/></title>
            </head>
            <body>
              <xsl:call-template name="body.attributes"/>
              <div class="{local-name(.)}">
                <xsl:apply-templates mode="titlepage.mode"/>
              </div>
            </body>
          </html>
        </xsl:with-param>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <div class="{local-name(.)}">
        <xsl:apply-templates mode="titlepage.mode"/>
      </div>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- CX-SBT: Add the label Authors -->
<xsl:template match="authorgroup" mode="titlepage.mode">
  <div class="{name(.)}">
    <p><b>Authors</b></p>
    <table>
        <xsl:apply-templates mode="titlepage.mode"/>
    </table>
    <p> </p>
  </div>
</xsl:template>

<!-- CX-SBT: Display the authors list -->
<xsl:template match="author" mode="titlepage.mode">
  <tr>
    <td><b><xsl:call-template name="person.name"/></b>
    </td>
    <td><xsl:apply-templates mode="titlepage.mode" select="./contrib"/></td>
    <td><xsl:apply-templates mode="titlepage.mode" select="./affiliation"/></td>
  </tr>
</xsl:template>

</xsl:stylesheet>
