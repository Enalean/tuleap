<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
                xmlns:xlink='http://www.w3.org/1999/xlink'
                exclude-result-prefixes="xlink"
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


<!-- CX-SBT: Email use the underline decoration -->
<xsl:template match="email">
  <fo:inline keep-together.within-line="always" hyphenate="false" text-decoration="underline">
    <xsl:apply-templates/>
  </fo:inline>
</xsl:template>

</xsl:stylesheet>

