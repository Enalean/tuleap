<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                xmlns:stext="http://nwalsh.com/xslt/ext/com.nwalsh.saxon.TextFactory"
                xmlns:xtext="com.nwalsh.xalan.Text"
                xmlns:lxslt="http://xml.apache.org/xslt"
                exclude-result-prefixes="xlink stext xtext lxslt"
                extension-element-prefixes="stext xtext"
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

<!-- ==================================================================== -->
<!-- Graphic format tests for the FO backend -->

<!-- CX-SBT: Use a bold font and center the block for the caption/para of the graphics (images, ...) -->
<xsl:template match="caption/para">
  <fo:block text-align="center" font-weight="bold" space-before.optimum="0.5em" space-before.minimum="0.4em" space-before.maximum="0.6em" font-size="10pt">
    <xsl:apply-templates/>
  </fo:block>
</xsl:template>

<xsl:template match="mediaobject|mediaobjectco">
  <!-- CX-SBT: Center the graphic -->
  <fo:block display-align="center" text-align="center">
    <xsl:call-template name="select.mediaobject"/>
    <xsl:apply-templates select="caption"/>
  </fo:block>
</xsl:template>

</xsl:stylesheet>
