<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:doc="http://nwalsh.com/xsl/documentation/1.0"
                exclude-result-prefixes="doc"
                version='1.0'>

<xsl:output method="html"
            encoding="UTF-8"
            indent="no"/>


<!--
//
// Copyright (c) Xerox Corporation, Codendi 2007-2008.
// This file is licensed under the GNU General Public License version 2. See the file COPYING. 
//
-->

<!-- ==================================================================== -->

<xsl:template name="user.footer.navigation">
  <xsl:param name="node" select="."/>
  <br/><div align="center"><font size="-1"><i>
  Copyright &#169; Xerox Corporation, Codendi 2001-2009. All Rights Reserved</i></font>
  </div>
</xsl:template>

<xsl:template name="user.header.navigation">
  <xsl:param name="node" select="."/>
  <div align="center"><font size="-1"><i>
  Copyright &#169; Xerox Corporation, Codendi, 2001-2009. All Rights Reserved</i></font>
  </div><br/>
</xsl:template>

<!-- ==================================================================== -->

</xsl:stylesheet>
