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

<xsl:template name="user.footer.navigation">
  <xsl:param name="node" select="."/>
  <br/><div align="center"><font size="-1"><i>
  Copyright &#169; Xerox Corporation, CodeX Team, 2001-2006. All Rights Reserved</i></font>
  </div>
</xsl:template>

<xsl:template name="user.header.navigation">
  <xsl:param name="node" select="."/>
  <div align="center"><font size="-1"><i>
  Copyright &#169; Xerox Corporation, CodeX Team, 2001-2006. All Rights Reserved</i></font>
  </div><br/>
</xsl:template>

<!-- ==================================================================== -->

</xsl:stylesheet>
