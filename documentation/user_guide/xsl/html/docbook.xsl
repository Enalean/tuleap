<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:doc="http://nwalsh.com/xsl/documentation/1.0"
                exclude-result-prefixes="doc"
                version='1.0'>

<xsl:output method="html"
            encoding="ISO-8859-1"
            indent="no"/>

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

<xsl:template match="*" mode="process.root">
  <xsl:variable name="doc" select="self::*"/>
  <html>
  <head>
    <xsl:call-template name="head.content">
      <xsl:with-param name="node" select="$doc"/>
    </xsl:call-template>
    <xsl:call-template name="user.head.content">
      <xsl:with-param name="node" select="$doc"/>
    </xsl:call-template>
  </head>
  <body>
    <xsl:call-template name="body.attributes"/>
    <xsl:call-template name="user.header.content">
      <xsl:with-param name="node" select="$doc"/>
    </xsl:call-template>
    <xsl:apply-templates select="."/>
    <xsl:call-template name="user.footer.content">
      <xsl:with-param name="node" select="$doc"/>
    </xsl:call-template>
  </body>
  </html>
</xsl:template>

<!-- ==================================================================== -->

</xsl:stylesheet>
