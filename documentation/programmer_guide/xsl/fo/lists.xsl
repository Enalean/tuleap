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


<!-- CX-SBT: Add a block for the step -->
<xsl:template match="step/para[1]"
              priority="2">
  <fo:block>
    <xsl:apply-templates/>
  </fo:block>
</xsl:template>

<xsl:template match="step">
  <xsl:variable name="id">
    <xsl:call-template name="object.id"/>
  </xsl:variable>

  <fo:list-item>
    <fo:list-item-label end-indent="label-end()">
      <!-- CX-SBT: Use specific attribute-set step.item.spacing -->
      <fo:block id="{$id}" xsl:use-attribute-sets="step.item.spacing">
        <!-- dwc: fix for one step procedures. Use a bullet if there's no step 2 -->
        <xsl:choose>
          <xsl:when test="count(../step) = 1">
            <xsl:text>&#x2022;</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="." mode="number">
              <xsl:with-param name="recursive" select="0"/>
            </xsl:apply-templates>.
          </xsl:otherwise>
        </xsl:choose>
      </fo:block>
    </fo:list-item-label>
    <fo:list-item-body start-indent="body-start()">
      <xsl:apply-templates/>
    </fo:list-item-body>
  </fo:list-item>
</xsl:template>

<xsl:template match="substeps">
  <!-- CX-SBT: Remove the use of xsl:use-attribute-sets="list.block.spacing" -->
  <fo:list-block provisional-distance-between-starts="2em"
                 provisional-label-separation="0.2em">
    <xsl:apply-templates/>
  </fo:list-block>
</xsl:template>

<xsl:template match="itemizedlist">
  <xsl:variable name="id"><xsl:call-template name="object.id"/></xsl:variable>

  <xsl:if test="title">
    <xsl:apply-templates select="title" mode="list.title.mode"/>
  </xsl:if>

  <xsl:variable name="space">
  <xsl:choose>
    <xsl:when test="@spacing = 'compact'">1</xsl:when>
    <xsl:otherwise>0</xsl:otherwise>
  </xsl:choose>
  </xsl:variable>

  <!-- CX-SBT: Remove the use of xsl:use-attribute-sets="list.block.spacing" and set a margin-left only for the first itemizedlist -->
  <!-- CX-SBT: And have a space before if we are in compact format -->
  <xsl:choose>
    <xsl:when test="count(ancestor::itemizedlist)=0">
      <fo:list-block id="{$id}" margin-left="2em"
                     provisional-distance-between-starts="1.5em"
                     provisional-label-separation="0.2em"
                     space-before.minimum="{$space}em"
                     space-before.optimum="{$space}em"
                     space-before.maximum="{$space}em">
        <xsl:apply-templates/>
      </fo:list-block>
    </xsl:when>
    <xsl:otherwise>
      <fo:list-block id="{$id}"
                     provisional-distance-between-starts="1.5em"
                     provisional-label-separation="0.2em"
                     space-before.minimum="{$space}em"
                     space-before.optimum="{$space}em"
                     space-before.maximum="{$space}em">
        <xsl:apply-templates/>
      </fo:list-block>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>


<!-- ==================================================================== -->

</xsl:stylesheet>

