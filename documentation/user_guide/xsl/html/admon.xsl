<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
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
    <table border="0" cellspacing="2" cellpadding="0">
      <tr height="100%">
        <td><img src="../icons/1pixel_black.gif" width="2" height="100%"/></td>
        <td>    
            <xsl:apply-templates/>
        </td>
      </tr>
    </table>
</xsl:template>

<xsl:template name="nongraphical.admonition">
  <div class="{name(.)}">
    <xsl:if test="$admon.style">
      <xsl:attribute name="style">
        <xsl:value-of select="$admon.style"/>
      </xsl:attribute>
    </xsl:if>

    <!-- CX-SBT: Add a table for having a border with a grey background -->
    <table border="1" cellspacing="0" cellpadding="5" bordercolor="#000000" bgcolor="#EEEEEE">
      <tr>
        <td>
            <h3 class="admonitiontitle">
              <xsl:call-template name="anchor"/>
              <xsl:apply-templates select="." mode="object.title.markup"/>
            </h3>
        
            <xsl:apply-templates/>
        </td>
      </tr>
    </table>
  </div>
</xsl:template>

<!-- CX-SBT: Add a style to the para of tip, important, warning and caution -->
<xsl:template match="tip/para"><p class="admonitionbody"><xsl:apply-templates/></p></xsl:template>
<xsl:template match="important/para"><p class="admonitionbody"><xsl:apply-templates/></p></xsl:template>
<xsl:template match="warning/para"><p class="admonitionbody"><xsl:apply-templates/></p></xsl:template>
<xsl:template match="caution/para"><p class="admonitionbody"><xsl:apply-templates/></p></xsl:template>

</xsl:stylesheet>
