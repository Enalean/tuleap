<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:doc="http://nwalsh.com/xsl/documentation/1.0"
                xmlns:exsl="http://exslt.org/common"
                xmlns:set="http://exslt.org/sets"
		version="1.0"
                exclude-result-prefixes="doc exsl set">

<!--
//
// Copyright (c) Xerox Corporation, Codendi 2007-2008.
// This file is licensed under the GNU General Public License version 2. See the file COPYING. 
//
//
//	Originally written by Stephane Bouhet 2002, Codendi Team, Xerox
//
-->

<xsl:import href="../../../lib/docbook-xsl/html/chunk.xsl"/>
<xsl:import href="../html/param.xsl"/>
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

<xsl:param name="l10n.gentext.language" select="'fr'"/>

</xsl:stylesheet>
