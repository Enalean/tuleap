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

<!-- CX-SBT: For example with programlisting, add a border with a grey background -->
<xsl:template match="example/programlisting">
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
            margin-left="3pc"
            margin-right="3pc"
        	padding="6pt"            
            wrap-option='no-wrap'
            white-space-collapse='false'
            linefeed-treatment="preserve"
            xsl:use-attribute-sets="monospace.verbatim.properties">
    <xsl:apply-templates/>
    </fo:block>
</xsl:template>

<xsl:template match="table">
  <xsl:variable name="id">
    <xsl:call-template name="object.id"/>
  </xsl:variable>

  <xsl:variable name="param.placement"
                select="substring-after(normalize-space($formal.title.placement),
                                        concat(local-name(.), ' '))"/>

  <xsl:variable name="placement">
    <xsl:choose>
      <xsl:when test="contains($param.placement, ' ')">
        <xsl:value-of select="substring-before($param.placement, ' ')"/>
      </xsl:when>
      <xsl:when test="$param.placement = ''">before</xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$param.placement"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <xsl:variable name="prop-columns"
    select=".//colspec[contains(@colwidth, '*')]"/>

  <xsl:variable name="table.content">
    <fo:block id="{$id}"
              xsl:use-attribute-sets="formal.object.properties"
              keep-together.within-column="1">

      <xsl:if test="$placement = 'before'">
        <xsl:call-template name="formal.object.heading"/>
      </xsl:if>

      <!-- CX-SBT: use the table attributes -->
      <fo:table border-collapse="collapse" xsl:use-attribute-sets="table.properties">
        <xsl:call-template name="table.frame"/>
        <xsl:if test="count($prop-columns) != 0">
          <xsl:attribute name="table-layout">fixed</xsl:attribute>
        </xsl:if>
        <xsl:apply-templates select="tgroup"/>
      </fo:table>

      <xsl:if test="$placement != 'before'">
        <xsl:call-template name="formal.object.heading"/>
      </xsl:if>
    </fo:block>
  </xsl:variable>

  <xsl:variable name="footnotes">
    <xsl:if test=".//footnote">
      <fo:block font-family="{$body.font.family}"
                font-size="{$footnote.font.size}"
                keep-together.within-column="1"
                keep-with-previous="always">
        <xsl:apply-templates select=".//footnote" mode="table.footnote.mode"/>
      </fo:block>
    </xsl:if>
  </xsl:variable>

  <xsl:choose>
    <xsl:when test="@orient='land'">
      <fo:block-container reference-orientation="90">
        <fo:block>
          <xsl:attribute name="span">
            <xsl:choose>
              <xsl:when test="@pgwide=1">all</xsl:when>
              <xsl:otherwise>none</xsl:otherwise>
            </xsl:choose>
          </xsl:attribute>
          <xsl:copy-of select="$table.content"/>
          <xsl:copy-of select="$footnotes"/>
        </fo:block>
      </fo:block-container>
    </xsl:when>
    <xsl:otherwise>
      <fo:block>
        <xsl:attribute name="span">
          <xsl:choose>
            <xsl:when test="@pgwide=1">all</xsl:when>
            <xsl:otherwise>none</xsl:otherwise>
          </xsl:choose>
        </xsl:attribute>
        <xsl:copy-of select="$table.content"/>
        <xsl:copy-of select="$footnotes"/>
      </fo:block>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>
