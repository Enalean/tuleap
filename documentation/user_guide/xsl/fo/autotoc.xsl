<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
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

<xsl:template name="toc.line">
  <xsl:variable name="id">
    <xsl:call-template name="object.id"/>
  </xsl:variable>

  <xsl:variable name="label">
    <xsl:apply-templates select="." mode="label.markup"/>
  </xsl:variable>

  <!-- CX-SBT: For level 1 (chapter), we use a bold font -->
  <xsl:variable name="depth" select="count(ancestor::*)"/>
  <xsl:variable name="weight">
  <xsl:choose>
    <xsl:when test="$depth=1">bold</xsl:when>
    <xsl:otherwise>normal</xsl:otherwise>
  </xsl:choose>
  </xsl:variable>

  <!-- CX-SBT: For level 1 (chapter), we have a space-before -->
  <xsl:variable name="space">
  <xsl:choose>
    <xsl:when test="$depth=1">0.5</xsl:when>
    <xsl:otherwise>0</xsl:otherwise>
  </xsl:choose>
  </xsl:variable>

  <!-- CX-SBT: For level 2 (section level 1), we use a small-caps -->
  <xsl:variable name="font-variant">
  <xsl:choose>
    <xsl:when test="$depth=2">small-caps</xsl:when>
    <xsl:otherwise>normal</xsl:otherwise>
  </xsl:choose>
  </xsl:variable>
  
  <!-- CX-SBT: For level 3 (section level 2), we use an italic font -->
  <xsl:variable name="style">
  <xsl:choose>
    <xsl:when test="$depth=3">italic</xsl:when>
    <xsl:otherwise>normal</xsl:otherwise>
  </xsl:choose>
  </xsl:variable>
  
      <fo:block text-align-last="justify"
                end-indent="{$toc.indent.width}pt"
                last-line-end-indent="-{$toc.indent.width}pt"
                font-weight="{$weight}"
                font-style="{$style}"
                space-before.minimum="{$space}em"
                space-before.optimum="{$space}em"
                space-before.maximum="{$space}em"
                font-variant="{$font-variant}" >
        <fo:inline keep-with-next.within-line="always">
          <fo:basic-link internal-destination="{$id}">
            <xsl:if test="$label != ''">
              <xsl:copy-of select="$label"/>
              <xsl:value-of select="$autotoc.label.separator"/>
            </xsl:if>
            <xsl:apply-templates select="." mode="title.markup"/>
          </fo:basic-link>
        </fo:inline>
        <fo:inline keep-together.within-line="always">
          <xsl:text> </xsl:text>
          <fo:leader leader-pattern="dots"
                     keep-with-next.within-line="always"/>
          <xsl:text> </xsl:text>
          <fo:basic-link internal-destination="{$id}">
            <fo:page-number-citation ref-id="{$id}"/>
          </fo:basic-link>
        </fo:inline>
      </fo:block>
</xsl:template>

<!-- CX-SBT: For figure, table, example and equation, we use the standard template : no italic or bold for the levels -->
<xsl:template name="toc.figure.line">
  <xsl:variable name="id">
    <xsl:call-template name="object.id"/>
  </xsl:variable>

  <xsl:variable name="label">
    <xsl:apply-templates select="." mode="label.markup"/>
  </xsl:variable>

      <fo:block text-align-last="justify"
                end-indent="{$toc.indent.width}pt"
                last-line-end-indent="-{$toc.indent.width}pt">
        <fo:inline keep-with-next.within-line="always">
          <fo:basic-link internal-destination="{$id}">
            <xsl:if test="$label != ''">
              <xsl:copy-of select="$label"/>
              <xsl:value-of select="$autotoc.label.separator"/>
            </xsl:if>
            <xsl:apply-templates select="." mode="title.markup"/>
          </fo:basic-link>
        </fo:inline>
        <fo:inline keep-together.within-line="always">
          <xsl:text> </xsl:text>
          <fo:leader leader-pattern="dots"
                     keep-with-next.within-line="always"/>
          <xsl:text> </xsl:text>
          <fo:basic-link internal-destination="{$id}">
            <fo:page-number-citation ref-id="{$id}"/>
          </fo:basic-link>
        </fo:inline>
      </fo:block>
</xsl:template>

<xsl:template match="figure|table|example|equation" mode="toc">
  <xsl:call-template name="toc.figure.line"/>
</xsl:template>

<!-- ==================================================================== -->

</xsl:stylesheet>

