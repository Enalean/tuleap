<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
                xmlns:doc="http://nwalsh.com/xsl/documentation/1.0"
                exclude-result-prefixes="doc"
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

<!-- CX-SBT: Import all the standard Docbook XSLT first -->
<xsl:import href="/usr/local/docbook-xsl/fo/docbook.xsl"/>

<!-- CX-SBT: And then import the specific CodeX XSLT -->
<xsl:import href="param.xsl"/>
<xsl:import href="admon.xsl"/>
<xsl:import href="pagesetup.xsl"/>
<xsl:import href="graphics.xsl"/>
<xsl:import href="../common/labels.xsl"/>
<xsl:import href="../common/l10n.xsl"/>
<xsl:import href="sections.xsl"/>
<xsl:import href="../common/gentext.xsl"/>
<xsl:import href="component.xsl"/>
<xsl:import href="titlepage.templates.xsl"/>
<xsl:import href="lists.xsl"/>
<xsl:import href="xref.xsl"/>
<xsl:import href="inline.xsl"/>
<xsl:import href="autotoc.xsl"/>
<xsl:import href="../common/common.xsl"/>
<xsl:import href="titlepage.xsl"/>
<xsl:import href="formal.xsl"/>
<xsl:import href="table.xsl"/>

<xsl:param name="l10n.gentext.language" select="'fr'"/>

<xsl:template match="/">
  <xsl:message>
    <xsl:text>Making </xsl:text>
    <xsl:value-of select="$page.orientation"/>
    <xsl:text> pages on </xsl:text>
    <xsl:value-of select="$paper.type"/>
    <xsl:text> paper (</xsl:text>
    <xsl:value-of select="$page.width"/>
    <xsl:text>x</xsl:text>
    <xsl:value-of select="$page.height"/>
    <xsl:text>)</xsl:text>
  </xsl:message>

  <xsl:variable name="document.element" select="*[1]"/>
  <xsl:variable name="title">
    <xsl:choose>
      <xsl:when test="$document.element/title[1]">
        <xsl:value-of select="$document.element/title[1]"/>
      </xsl:when>
      <xsl:otherwise>[could not find document title]</xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <fo:root font-family="{$body.font.family}"
           font-size="{$body.font.size}"
           text-align="{$alignment}"
           line-height="{$line-height}">
    <xsl:attribute name="language">
      <xsl:call-template name="l10n.language">
        <xsl:with-param name="target" select="/*[1]"/>
      </xsl:call-template>
    </xsl:attribute>

    <xsl:if test="$xep.extensions != 0">
      <xsl:call-template name="xep-document-information"/>
    </xsl:if>
    <xsl:call-template name="setup.pagemasters"/>
    <xsl:choose>
      <xsl:when test="$rootid != ''">
        <xsl:choose>
          <xsl:when test="count(key('id',$rootid)) = 0">
            <xsl:message terminate="yes">
              <xsl:text>ID '</xsl:text>
              <xsl:value-of select="$rootid"/>
              <xsl:text>' not found in document.</xsl:text>
            </xsl:message>
          </xsl:when>
          <xsl:otherwise>
            <xsl:if test="$fop.extensions != 0">
              <xsl:apply-templates select="key('id',$rootid)" mode="fop.outline"/>
            </xsl:if>
            <xsl:if test="$xep.extensions != 0">
              <xsl:variable name="bookmarks">
                <xsl:apply-templates select="key('id',$rootid)" mode="xep.outline"/>
              </xsl:variable>
              <xsl:if test="string($bookmarks) != ''">
                <rx:outline xmlns:rx="http://www.renderx.com/XSL/Extensions">
                  <xsl:copy-of select="$bookmarks"/>
                </rx:outline>
              </xsl:if>
            </xsl:if>
            <xsl:apply-templates select="key('id',$rootid)"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:if test="$fop.extensions != 0">
          <xsl:apply-templates mode="fop.outline"/>
        </xsl:if>
        <xsl:if test="$xep.extensions != 0">
          <xsl:variable name="bookmarks">
            <xsl:apply-templates mode="xep.outline"/>
          </xsl:variable>
          <xsl:if test="string($bookmarks) != ''">
            <rx:outline xmlns:rx="http://www.renderx.com/XSL/Extensions">
              <xsl:copy-of select="$bookmarks"/>
            </rx:outline>
          </xsl:if>
        </xsl:if>
        <xsl:apply-templates/>
      </xsl:otherwise>
    </xsl:choose>

  <!-- CX-SBT: Add a blank page at the end of the document -->
  <fo:page-sequence language="en"
                    hyphenate="true"
                    master-reference="oneside1"
                    initial-page-number="1">
    <fo:flow flow-name="xsl-region-body">

      <fo:block>
      </fo:block>
    </fo:flow>
  </fo:page-sequence>

  </fo:root>
</xsl:template>

</xsl:stylesheet>
