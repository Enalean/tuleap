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

<xsl:template match="ulink" name="ulink">
  <!-- CX-SBT: Use the ulink.properties attributes instead of xref one -->
  <fo:basic-link external-destination="{@url}"
                 xsl:use-attribute-sets="ulink.properties">
    <xsl:choose>
      <xsl:when test="count(child::node())=0">
        <xsl:call-template name="hyphenate-url">
          <xsl:with-param name="url" select="@url"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
	<xsl:apply-templates/>
      </xsl:otherwise>
    </xsl:choose>
  </fo:basic-link>
  <xsl:if test="count(child::node()) != 0
                and string(.) != @url
                and $ulink.show != 0">
    <fo:inline hyphenate="false">
      <xsl:text> [</xsl:text>
      <xsl:call-template name="hyphenate-url">
        <xsl:with-param name="url" select="@url"/>
      </xsl:call-template>
      <xsl:text>]</xsl:text>
    </fo:inline>
  </xsl:if>
</xsl:template>


<xsl:template match="xref" name="xref">
  <xsl:variable name="targets" select="key('id',@linkend)"/>
  <xsl:variable name="target" select="$targets[1]"/>
  <xsl:variable name="refelem" select="local-name($target)"/>

  <xsl:call-template name="check.id.unique">
    <xsl:with-param name="linkend" select="@linkend"/>
  </xsl:call-template>

  <xsl:choose>
    <xsl:when test="$refelem=''">
      <xsl:message>
	<xsl:text>XRef to nonexistent id: </xsl:text>
	<xsl:value-of select="@linkend"/>
      </xsl:message>
      <xsl:text>???</xsl:text>
    </xsl:when>

    <xsl:when test="$target/@xreflabel">
      <fo:basic-link internal-destination="{@linkend}"
                     xsl:use-attribute-sets="xref.properties">
	<xsl:call-template name="xref.xreflabel">
	  <xsl:with-param name="target" select="$target"/>
	</xsl:call-template>
      </fo:basic-link>
    </xsl:when>

    <xsl:otherwise>
      <fo:basic-link internal-destination="{@linkend}"
                     xsl:use-attribute-sets="xref.properties">
        <xsl:choose>
	  <xsl:when test="@endterm">
	    <xsl:variable name="etargets" select="key('id',@endterm)"/>
	    <xsl:variable name="etarget" select="$etargets[1]"/>
	    <xsl:choose>
	      <xsl:when test="count($etarget) = 0">
		<xsl:message>
		  <xsl:value-of select="count($etargets)"/>
		  <xsl:text>Endterm points to nonexistent ID: </xsl:text>
		  <xsl:value-of select="@endterm"/>
		</xsl:message>
		<xsl:text>???</xsl:text>
	      </xsl:when>
	      <xsl:otherwise>
		<xsl:apply-templates select="$etarget" mode="endterm"/>
	      </xsl:otherwise>
	    </xsl:choose>
	  </xsl:when>

          <xsl:otherwise>
            <xsl:apply-templates select="$target" mode="xref-to"/>
          </xsl:otherwise>
        </xsl:choose>

        <!-- CX-SBT: call the template to insert the page citation -->
        <xsl:call-template name="insert.page.citation">
          <xsl:with-param name="id" select="@linkend"/>
        </xsl:call-template>

      </fo:basic-link>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template name="insert.page.citation">
  <xsl:param name="id" select="'???'"/>
  <xsl:if test="$insert.xref.page.number">
    <xsl:text> </xsl:text>
    <fo:inline keep-together.within-line="always">
      <xsl:text>[</xsl:text>
      <xsl:call-template name="gentext">
        <xsl:with-param name="key" select="'page.citation'"/>
      </xsl:call-template>
      <!-- CX-SBT: We use the &nbsp; character to avoid some fop problem -->
      <xsl:text>&#160;</xsl:text>
      <fo:page-number-citation ref-id="{$id}"/>
      <xsl:text>]</xsl:text>
    </fo:inline>
  </xsl:if>
</xsl:template>


</xsl:stylesheet>
