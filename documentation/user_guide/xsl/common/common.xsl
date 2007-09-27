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

<!-- CX-SBT: Get the version of the last revision -->
<xsl:template name="get.last.version.revision">
  <xsl:variable name="last_revision">
    <xsl:for-each select="bookinfo/revhistory/revision">
        <xsl:if test="position()=last()">
            <xsl:value-of select="revnumber"/>
        </xsl:if>
    </xsl:for-each>
  </xsl:variable>
    
  <xsl:value-of select="$last_revision"/>

</xsl:template>

<!-- CX-SBT: Get the date of the last revision -->
<xsl:template name="get.last.date.revision">
  <xsl:variable name="last_date">
    <xsl:for-each select="bookinfo/revhistory/revision">
        <xsl:if test="position()=last()">
            <xsl:value-of select="date"/>
        </xsl:if>
    </xsl:for-each>
  </xsl:variable>

  <xsl:value-of select="$last_date"/>

</xsl:template>

</xsl:stylesheet>

