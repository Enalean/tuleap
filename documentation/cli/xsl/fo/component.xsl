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

<xsl:template name="component.title">
  <xsl:param name="node" select="."/>
  <xsl:param name="pagewide" select="0"/>
  <xsl:variable name="id">
    <xsl:call-template name="object.id">
      <xsl:with-param name="object" select="$node"/>
    </xsl:call-template>
  </xsl:variable>

  <!-- CX-SBT: We retrieve the chapter number -->
  <xsl:variable name="title.number">
    <xsl:apply-templates select="$node" mode="object.title.number.markup">
      <xsl:with-param name="allow-anchors" select="1"/>
    </xsl:apply-templates>
  </xsl:variable>
  <!-- CX-SBT: We retrieve the chapter label -->
  <xsl:variable name="title.text">
    <xsl:apply-templates select="$node" mode="object.title.text.markup">
      <xsl:with-param name="allow-anchors" select="1"/>
    </xsl:apply-templates>
  </xsl:variable>

  <!-- CX-SBT: Put the id in this block (for having the page number in the toc - See below also)
               Adjust also the margin  -->
  <fo:block id="{$id}" keep-with-next.within-column="always" hyphenate="false" margin-left="0pc">
    <xsl:if test="$pagewide != 0">
      <xsl:attribute name="span">all</xsl:attribute>
    </xsl:if>
    <!-- CX-SBT: Print the Chapter number -->
    <xsl:copy-of select="$title.number"/>
  </fo:block>
  
  <!-- CX-SBT: Compute padding (if no title.text then no padding) -->
  <xsl:variable name="padding-bottom">
    <xsl:choose>
      <xsl:when test="$title.text = ''">0pc
      </xsl:when>
      <xsl:otherwise>6pc
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable> 
  <xsl:variable name="padding-top">
    <xsl:choose>
      <xsl:when test="$title.text = ''">0.5pc
      </xsl:when>
      <xsl:otherwise>2pc
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  
  <!-- CX-SBT: Print the Chapter label + line after -->
  <fo:block padding-left="3pc" padding-top="{$padding-top}" padding-bottom="{$padding-bottom}" border-after-color="black" border-after-width="0.05em" border-after-style="solid" margin-left="0pc"> 
    <xsl:copy-of select="$title.text"/>
  </fo:block>
</xsl:template>

<!-- ==================================================================== -->

<xsl:template match="chapter">
  <xsl:variable name="id">
    <xsl:call-template name="object.id"/>
  </xsl:variable>
  <xsl:variable name="master-reference">
    <xsl:call-template name="select.pagemaster"/>
  </xsl:variable>

  <!-- CX-SBT: Remove the id in the page-sequence (for having the page number in the toc - See before also) -->
  <fo:page-sequence 
                    hyphenate="{$hyphenate}"
                    master-reference="{$master-reference}">
    <xsl:attribute name="language">
      <xsl:call-template name="l10n.language"/>
    </xsl:attribute>

    <!-- if there is a preceding chapter or this chapter appears in a part, the -->
    <!-- page numbering will already be adjusted -->
    <xsl:if test="not(preceding::chapter) and not(parent::part)">
      <xsl:attribute name="initial-page-number">1</xsl:attribute>
    </xsl:if>
    <xsl:if test="$double.sided != 0">
      <xsl:attribute name="force-page-count">end-on-even</xsl:attribute>
    </xsl:if>

    <xsl:apply-templates select="." mode="running.head.mode">
      <xsl:with-param name="master-reference" select="$master-reference"/>
    </xsl:apply-templates>
    <xsl:apply-templates select="." mode="running.foot.mode">
      <xsl:with-param name="master-reference" select="$master-reference"/>
    </xsl:apply-templates>

    <fo:flow flow-name="xsl-region-body">
      <xsl:call-template name="component.separator"/>
      <xsl:call-template name="chapter.titlepage"/>

      <xsl:variable name="toc.params">
        <xsl:call-template name="find.path.params">
          <xsl:with-param name="table" select="normalize-space($generate.toc)"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:if test="contains($toc.params, 'toc')">
        <xsl:call-template name="component.toc"/>
      </xsl:if>
      <xsl:apply-templates/>
    </fo:flow>
  </fo:page-sequence>
</xsl:template>

</xsl:stylesheet>

