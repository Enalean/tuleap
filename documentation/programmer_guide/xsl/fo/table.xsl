<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:doc="http://nwalsh.com/xsl/documentation/1.0"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
                xmlns:stbl="http://nwalsh.com/xslt/ext/com.nwalsh.saxon.Table"
                xmlns:xtbl="com.nwalsh.xalan.Table"
                xmlns:lxslt="http://xml.apache.org/xslt"
                exclude-result-prefixes="doc stbl xtbl lxslt"
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

<xsl:template match="thead">
  <xsl:variable name="tgroup" select="parent::*"/>

  <!-- CX-SBT: use the table header attributes -->
  <fo:table-header xsl:use-attribute-sets="table.header">
    <xsl:apply-templates select="row[1]">
      <xsl:with-param name="spans">
        <xsl:call-template name="blank.spans">
          <xsl:with-param name="cols" select="../@cols"/>
        </xsl:call-template>
      </xsl:with-param>
    </xsl:apply-templates>
  </fo:table-header>
</xsl:template>

<!-- ==================================================================== -->

</xsl:stylesheet>
