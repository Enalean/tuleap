<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
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
<!-- ============================================================ -->
<!-- label markup -->

<xsl:template match="section" mode="label.markup">
  <!-- CX-SBT: Test if this section has a number - Call template label.this.section -->
  <!-- does this section get labelled? -->
  <xsl:variable name="depth" select="count(ancestor::section) + 1"/>
  <xsl:variable name="label">
    <xsl:call-template name="label.this.section">
      <xsl:with-param name="section" select="."/>
      <xsl:with-param name="depth" select="$depth"/>
    </xsl:call-template>
  </xsl:variable>

  <xsl:if test="$label != 0">
      <!-- if this is a nested section, label the parent -->
      <xsl:if test="local-name(..) = 'section'">
        <xsl:variable name="parent.section.label">
          <xsl:apply-templates select=".." mode="label.markup"/>
        </xsl:variable>
        <xsl:if test="$parent.section.label != ''">
          <xsl:apply-templates select=".." mode="label.markup"/>
          <xsl:apply-templates select=".." mode="intralabel.punctuation"/>
        </xsl:if>
      </xsl:if>

      <!-- if the parent is a component, maybe label that too -->
      <xsl:variable name="parent.is.component">
        <xsl:call-template name="is.component">
          <xsl:with-param name="node" select=".."/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:if test="$section.label.includes.component.label != 0
                    and $parent.is.component != 0">
        <xsl:variable name="parent.label">
          <xsl:apply-templates select=".." mode="label.markup"/>
        </xsl:variable>
        <xsl:if test="$parent.label != ''">
          <xsl:apply-templates select=".." mode="label.markup"/>
          <xsl:apply-templates select=".." mode="intralabel.punctuation"/>
        </xsl:if>
      </xsl:if>
  </xsl:if>

<!--
  <xsl:message>
    <xsl:value-of select="title"/>, <xsl:value-of select="$label"/>, <xsl:number count="section"/>, <xsl:value-of select="$depth"/>
  </xsl:message>
-->

  <xsl:choose>
    <xsl:when test="@label">
      <xsl:value-of select="@label"/>
    </xsl:when>
    <xsl:when test="$label != 0">
      <xsl:number count="section"/>
    </xsl:when>
  </xsl:choose>
</xsl:template>


<!-- ============================================================ -->

<!-- CX-SBT: Section numerotation test : for section < 3, yes  -->
<xsl:template name="label.this.section">
  <xsl:param name="section" select="."/>
  <xsl:param name="depth" select="."/>
  <xsl:choose>
     <xsl:when test="$section.autolabel != 0">
        <xsl:choose>
           <xsl:when test="$depth &lt; 3">
              <xsl:value-of select="1"/>
           </xsl:when>
           <xsl:otherwise>
              <xsl:value-of select="0"/>
           </xsl:otherwise>
        </xsl:choose>
     </xsl:when>
     <xsl:otherwise>
        <xsl:value-of select="0"/>
     </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- ============================================================ -->

<!-- CX-SBT: For the figure, example and table, we numerate without the chapter prefix, but we use an incremental number (1,2,3,...)  -->
<xsl:template match="figure|example|table"
              mode="label.markup">
  <xsl:variable name="pchap"
                select="ancestor::chapter
                        |ancestor::appendix
                        |ancestor::article[ancestor::book]"/>

  <xsl:variable name="prefix">
  </xsl:variable>

  <xsl:choose>
    <xsl:when test="@label">
      <xsl:value-of select="@label"/>
    </xsl:when>
    <xsl:otherwise>
      <xsl:choose>
        <xsl:when test="count($pchap)>0">
          <xsl:if test="$prefix != ''">
            <xsl:apply-templates select="$pchap" mode="label.markup"/>
            <xsl:apply-templates select="$pchap" mode="intralabel.punctuation"/>
          </xsl:if>
          <xsl:number format="1" from="book|article" level="any"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:number format="1" from="book|article" level="any"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>
