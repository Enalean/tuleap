<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:doc="http://nwalsh.com/xsl/documentation/1.0"
                xmlns:exsl="http://exslt.org/common"
                xmlns:set="http://exslt.org/sets"
		version="1.0"
                exclude-result-prefixes="doc exsl set">

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

<xsl:import href="/usr/local/docbook-xsl/html/onechunk.xsl"/>
<xsl:import href="../html/param_onechunk.xsl"/>
<xsl:import href="../common/labels.xsl"/>
<xsl:import href="../html/admon.xsl"/>
<xsl:import href="../html/titlepage.xsl"/>
<xsl:import href="../html/docbook.xsl"/>
<xsl:import href="../html/chunk-common.xsl"/>
<xsl:import href="../html/titlepage.templates.xsl"/>
<xsl:import href="../common/common.xsl"/>
<xsl:import href="../common/l10n.xsl"/>
<xsl:import href="../html/formal.xsl"/>
<xsl:import href="../html/table.xsl"/>
<xsl:import href="../html/graphics.xsl"/>

<xsl:param name="l10n.gentext.language" select="'en'"/>

</xsl:stylesheet>
