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

<!-- CX-SBT: match witout note - See below -->
<xsl:template match="important|warning|caution|tip">
  <xsl:choose>
    <xsl:when test="$admon.graphics != 0">
      <xsl:call-template name="graphical.admonition"/>
    </xsl:when>
    <xsl:otherwise>
      <xsl:call-template name="nongraphical.admonition"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- CX-SBT: Notes have a line on the left border -->
<xsl:template match="note">
  <fo:block
	border-left-color="black" 
	border-left-width="0.1em"
	border-left-style="solid" 
	padding-left="0.5pc" >
    <xsl:apply-templates/>
  </fo:block>
</xsl:template>


<xsl:template name="admon.graphic.width">
  <xsl:param name="node" select="."/>
  <xsl:text>36pt</xsl:text>
</xsl:template>

<xsl:template name="admon.graphic">
  <xsl:param name="node" select="."/>

  <xsl:variable name="filename">
    <xsl:value-of select="$admon.graphics.path"/>
    <xsl:choose>
      <xsl:when test="name($node)='note'">note</xsl:when>
      <xsl:when test="name($node)='warning'">warning</xsl:when>
      <xsl:when test="name($node)='caution'">caution</xsl:when>
      <xsl:when test="name($node)='tip'">tip</xsl:when>
      <xsl:when test="name($node)='important'">important</xsl:when>
      <xsl:otherwise>note</xsl:otherwise>
    </xsl:choose>
    <xsl:value-of select="$admon.graphics.extension"/>
  </xsl:variable>

  <xsl:choose>
    <xsl:when test="$passivetex.extensions != 0
                    or $fop.extensions != 0
                    or $arbortext.extensions != 0">
      <xsl:value-of select="$filename"/>
    </xsl:when>
    <xsl:otherwise>
      <xsl:text>url(</xsl:text>
      <xsl:value-of select="$filename"/>
      <xsl:text>)</xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template name="graphical.admonition">
  <xsl:variable name="id">
    <xsl:call-template name="object.id"/>
  </xsl:variable>

  <fo:block id="{$id}">
    <fo:table>
      <fo:table-body>
        <fo:table-row>
          <fo:table-cell number-rows-spanned="2">
            <fo:block>
              <fo:external-graphic width="auto" height="auto">
                <xsl:attribute name="src">
                  <xsl:call-template name="admon.graphic"/>
                </xsl:attribute>
                <xsl:attribute name="content-width">
                  <xsl:call-template name="admon.graphic.width"/>
                </xsl:attribute>
              </fo:external-graphic>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block xsl:use-attribute-sets="admonition.title.properties">
              <xsl:apply-templates select="." mode="object.title.markup"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
        <fo:table-row>
          <fo:table-cell number-columns-spanned="2">
            <fo:block xsl:use-attribute-sets="admonition.properties">
              <xsl:apply-templates/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-body>
    </fo:table>
  </fo:block>
</xsl:template>

<xsl:template name="nongraphical.admonition">
  <xsl:variable name="id">
    <xsl:call-template name="object.id"/>
  </xsl:variable>

  <!-- CX-SBT: Draw a block with border and light gray background -->
  <fo:block space-before.minimum="0.8em"
            space-before.optimum="1em"
            space-before.maximum="1.2em"
            start-indent="0.25in"
            end-indent="0.25in"
            background-color="rgb(238, 238, 238)"
            border-after-color="black" 
		    border-after-width="0.1em" 
		    border-before-color="black" 
		    border-before-width="0.1em" 
        	border-end-color="black" 
        	border-end-width="0.1em" 
        	border-start-color="black" 
        	border-start-width="0.1em"
        	border-style="solid" 
        	padding="6pt"            
            id="{$id}"
            margin-left="4pc"
            margin-right="4pc">
    <fo:block keep-with-next='always'
              xsl:use-attribute-sets="admonition.title.properties">
      <xsl:apply-templates select="." mode="object.title.markup"/>
    </fo:block>

    <fo:block xsl:use-attribute-sets="admonition.properties">
      <xsl:apply-templates/>
    </fo:block>
  </fo:block>
</xsl:template>

<xsl:template match="note/title"></xsl:template>
<xsl:template match="important/title"></xsl:template>
<xsl:template match="warning/title"></xsl:template>
<xsl:template match="caution/title"></xsl:template>
<xsl:template match="tip/title"></xsl:template>

</xsl:stylesheet>
