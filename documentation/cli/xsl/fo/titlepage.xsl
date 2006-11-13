<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
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

<!-- ==================================================================== -->

<xsl:template match="revhistory" mode="titlepage.mode">

  <!-- CX-SBT: Add the revision history title -->
  <fo:block text-decoration="underline"
    font-family="Helvetica"
    font-weight="bold" font-size="12pt">
    <xsl:call-template name="gentext">
      <xsl:with-param name="key" select="'RevHistory'"/>
    </xsl:call-template>
  </fo:block>


  <fo:table
    border-bottom-width="0.5pt"
    width="100%"
    border-right-style="solid"
    border-top-style="solid"
    border-bottom-style="solid"
    border-collapse="collapse"
    space-after.maximum="2em"
    border-left-style="solid"
    space-after.minimum="0.5em"
    space-before.optimum="1em"
    space-before.maximum="2em"
    space-before.minimum="0.5em"
    border-right-width="0.5pt"
    border-top-width="0.5pt"
    space-after.optimum="1em"
    border-left-width="0.5pt"
  >
    
    <fo:table-column column-number="1"
                     column-width="70pt" />

    <fo:table-column column-number="2"
                     column-width="70pt" />

    <fo:table-column column-number="3"
                     column-width="250pt" />

    <fo:table-column column-number="4"
                     column-width="90pt" />

    <fo:table-header
	background-color="rgb(238, 238, 238)"
	font-weight="bold"
	font-size="10pt"
	text-align="center">

      <fo:table-row>
        <fo:table-cell 
        padding-right="2pt"
        padding-top="2pt"
        border-bottom-color="black"
        border-right-style="solid"
        border-right-color="black"
        padding-left="2pt"
        border-bottom-style="solid"
        border-bottom-width="0.5pt"
        border-right-width="0.5pt"
        padding-bottom="2pt">
          <fo:block>
		<xsl:call-template name="gentext">
		 <xsl:with-param name="key" select="'Version'"/>
		</xsl:call-template>
	  </fo:block>
        </fo:table-cell>

        <fo:table-cell 
        padding-right="2pt"
        padding-top="2pt"
        border-bottom-color="black"
        border-right-style="solid"
        border-right-color="black"
        padding-left="2pt"
        border-bottom-style="solid"
        border-bottom-width="0.5pt"
        border-right-width="0.5pt"
        padding-bottom="2pt">
          <fo:block>
		<xsl:call-template name="gentext">
		 <xsl:with-param name="key" select="'Date'"/>
		</xsl:call-template>
	  </fo:block>
        </fo:table-cell>

        <fo:table-cell 
        padding-right="2pt"
        padding-top="2pt"
        border-bottom-color="black"
        border-right-style="solid"
        border-right-color="black"
        padding-left="2pt"
        border-bottom-style="solid"
        border-bottom-width="0.5pt"
        border-right-width="0.5pt"
        padding-bottom="2pt">
          <fo:block>
		<xsl:call-template name="gentext">
		 <xsl:with-param name="key" select="'Description'"/>
		</xsl:call-template>
	  </fo:block>
        </fo:table-cell>

        <fo:table-cell 
        padding-right="2pt"
        padding-top="2pt"
        border-bottom-color="black"
        border-right-style="solid"
        border-right-color="black"
        padding-left="2pt"
        border-bottom-style="solid"
        border-bottom-width="0.5pt"
        border-right-width="0.5pt"
        padding-bottom="2pt">
          <fo:block>
<xsl:call-template name="gentext">
 <xsl:with-param name="key" select="'Author'"/>
</xsl:call-template>
	  </fo:block>
        </fo:table-cell>
      </fo:table-row>
    </fo:table-header>

    <fo:table-body>
      <xsl:apply-templates mode="titlepage.mode"/>
    </fo:table-body>
  </fo:table>
</xsl:template>

<xsl:template match="revhistory/revision" mode="titlepage.mode">
  <xsl:variable name="revnumber" select=".//revnumber"/>
  <xsl:variable name="revdate"   select=".//date"/>
  <xsl:variable name="revauthor" select=".//authorinitials"/>
  <xsl:variable name="revremark" select=".//revremark"/>
  <fo:table-row font-size="8pt">
    <fo:table-cell
        padding-right="2pt"
        padding-top="2pt"
        border-bottom-color="black"
        border-right-style="solid"
        border-right-color="black"
        padding-left="2pt"
        border-bottom-style="solid"
        border-bottom-width="0.5pt"
        border-right-width="0.5pt"
        padding-bottom="2pt">
      <fo:block>
        <xsl:if test="$revnumber">
          <xsl:apply-templates select="$revnumber[1]" mode="titlepage.mode"/>
        </xsl:if>
      </fo:block>
    </fo:table-cell>
    <fo:table-cell
        padding-right="2pt"
        padding-top="2pt"
        border-bottom-color="black"
        border-right-style="solid"
        border-right-color="black"
        padding-left="2pt"
        border-bottom-style="solid"
        border-bottom-width="0.5pt"
        border-right-width="0.5pt"
        padding-bottom="2pt">
      <fo:block>
        <xsl:apply-templates select="$revdate[1]" mode="titlepage.mode"/>
      </fo:block>
    </fo:table-cell>
    <fo:table-cell
        padding-right="2pt"
        padding-top="2pt"
        border-bottom-color="black"
        border-right-style="solid"
        border-right-color="black"
        padding-left="2pt"
        border-bottom-style="solid"
        border-bottom-width="0.5pt"
        border-right-width="0.5pt"
        padding-bottom="2pt">
      <fo:block>
          <xsl:apply-templates select="$revremark[1]" mode="titlepage.mode"/>
      </fo:block>
    </fo:table-cell>
    <fo:table-cell
        padding-right="2pt"
        padding-top="2pt"
        border-bottom-color="black"
        border-right-style="solid"
        border-right-color="black"
        padding-left="2pt"
        border-bottom-style="solid"
        border-bottom-width="0.5pt"
        border-right-width="0.5pt"
        padding-bottom="2pt">
      <fo:block>
        <xsl:apply-templates select="$revauthor[1]" mode="titlepage.mode"/>
      </fo:block>
    </fo:table-cell>
  </fo:table-row>
</xsl:template>

<xsl:template match="revision/revnumber" mode="titlepage.mode">
  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

<xsl:template match="revision/date" mode="titlepage.mode">
  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

<xsl:template match="revision/authorinitials" mode="titlepage.mode">
  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

<xsl:template match="revision/revremark" mode="titlepage.mode">
  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

<!-- ==================================================================== -->


<xsl:template name="verso.authorgroup">

  <!-- CX-SBT: Create the authors list -->
  <fo:block text-decoration="underline"
    font-family="Helvetica"
    font-weight="bold" font-size="12pt"
    space-after="6pt">
	<xsl:call-template name="gentext">
	 <xsl:with-param name="key" select="'Authors'"/>
	</xsl:call-template>
  </fo:block>

  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

<xsl:template match="authorgroup/author" mode="titlepage.mode">
  <xsl:variable name="firstname" select=".//firstname"/>
  <xsl:variable name="surname"   select=".//surname"/>
  <xsl:variable name="orgname" select=".//affiliation/orgname"/>

  <fo:block>
    <xsl:if test="$firstname">
      <xsl:apply-templates select="$firstname[1]" mode="titlepage.mode"/>
    </xsl:if>
    <xsl:call-template name="gentext.space"/>
    <xsl:if test="$surname">
      <xsl:apply-templates select="$surname[1]" mode="titlepage.mode"/>
    </xsl:if>
    <xsl:call-template name="gentext.template">
      <xsl:with-param name="context" select="'authorgroup'"/>
      <xsl:with-param name="name" select="'sep'"/>
    </xsl:call-template>
    <xsl:if test="$orgname">
      <xsl:apply-templates select="$orgname[1]" mode="titlepage.mode"/>
    </xsl:if>
  </fo:block>

</xsl:template>

<xsl:template match="author/firstname" mode="titlepage.mode">
  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

<xsl:template match="author/surname" mode="titlepage.mode">
  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

<xsl:template match="author/authorinitials" mode="titlepage.mode">
  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

<xsl:template match="author/affiliation/orgname" mode="titlepage.mode">
  <xsl:apply-templates mode="titlepage.mode"/>
</xsl:template>

</xsl:stylesheet>
