<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:doc="http://nwalsh.com/xsl/documentation/1.0"
                exclude-result-prefixes="doc"
                version='1.0'>

<!--
//
// Copyright (c) Xerox Corporation, Codendi 2007-2008.
// This file is licensed under the GNU General Public License version 2. See the file COPYING. 
//
// $Id: 
//
//	Originally written by Stephane Bouhet 2002, Codendi Team, Xerox
//
-->
<!-- ============================================================ -->

<!-- CX-SBT: Calculate the Chapter label + chapter number -->
<xsl:template match="chapter" mode="object.title.number.markup">
  <xsl:param name="allow-anchors" select="0"/>
  <xsl:variable name="template">
    CHAPTER %n
  </xsl:variable>

  <xsl:call-template name="substitute-markup">
    <xsl:with-param name="allow-anchors" select="$allow-anchors"/>
    <xsl:with-param name="template" select="$template"/>
  </xsl:call-template>
</xsl:template>

<!-- CX-SBT: Calculate the chapter number -->
<xsl:template match="chapter" mode="object.title.text.markup">
  <xsl:param name="allow-anchors" select="0"/>
  <xsl:variable name="template">
    %t
  </xsl:variable>

  <xsl:call-template name="substitute-markup">
    <xsl:with-param name="allow-anchors" select="$allow-anchors"/>
    <xsl:with-param name="template" select="$template"/>
  </xsl:call-template>
</xsl:template>

<!-- CX-SBT: For bibliography, we use only the title -->
<xsl:template match="bibliography" mode="object.title.number.markup">
<xsl:value-of select="title"/>
</xsl:template>

<!-- CX-SBT: For bibliography, nothing after the title -->
<xsl:template match="bibliography" mode="object.title.text.markup">
</xsl:template>

<!-- ============================================================ -->

</xsl:stylesheet>

