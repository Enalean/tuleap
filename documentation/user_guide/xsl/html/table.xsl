<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:doc="http://nwalsh.com/xsl/documentation/1.0"
                xmlns:stbl="http://nwalsh.com/xslt/ext/com.nwalsh.saxon.Table"
                xmlns:xtbl="com.nwalsh.xalan.Table"
                xmlns:lxslt="http://xml.apache.org/xslt"
                exclude-result-prefixes="doc stbl xtbl lxslt"
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

<xsl:template match="tgroup">
  <table>
    <xsl:choose>
      <!-- If there's a textobject/phrase for the table summary, use it -->
      <xsl:when test="../textobject/phrase">
        <xsl:attribute name="summary">
          <xsl:value-of select="../textobject/phrase"/>
        </xsl:attribute>
      </xsl:when>

      <!-- If there's a <?dbhtml table-summary="foo"?> PI, use it for
           the HTML table summary attribute -->
      <xsl:when test="processing-instruction('dbhtml')">
        <xsl:variable name="summary">
          <xsl:call-template name="dbhtml-attribute">
            <xsl:with-param name="pis"
                            select="processing-instruction('dbhtml')[1]"/>
            <xsl:with-param name="attribute" select="'table-summary'"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:if test="$summary != ''">
          <xsl:attribute name="summary">
            <xsl:value-of select="$summary"/>
          </xsl:attribute>
        </xsl:if>
      </xsl:when>

      <!-- Otherwise, if there's a title, use that -->
      <xsl:when test="../title">
        <xsl:attribute name="summary">
          <xsl:value-of select="string(../title)"/>
        </xsl:attribute>
      </xsl:when>

      <!-- Otherwise, forget the whole idea -->
      <xsl:otherwise><!-- nevermind --></xsl:otherwise>
    </xsl:choose>

    <!-- CX-SBT: Choose the border color and padding -->
    <xsl:attribute name="BorderColorDark">#CCCCCC</xsl:attribute>
    <xsl:attribute name="BorderColorLight">#CCCCCC</xsl:attribute>
    <xsl:attribute name="cellpadding">1</xsl:attribute>
    <xsl:attribute name="cellspacing">0</xsl:attribute>

    <xsl:if test="../@pgwide=1">
      <xsl:attribute name="width">100%</xsl:attribute>
    </xsl:if>

    <xsl:choose>
      <xsl:when test="$table.borders.with.css != 0">
        <xsl:attribute name="border">0</xsl:attribute>
        <xsl:choose>
          <xsl:when test="../@frame='all'">
            <xsl:attribute name="style">
              <xsl:text>border-collapse: collapse;</xsl:text>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'top'"/>
              </xsl:call-template>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'bottom'"/>
              </xsl:call-template>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'left'"/>
              </xsl:call-template>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'right'"/>
              </xsl:call-template>
            </xsl:attribute>
          </xsl:when>
          <xsl:when test="../@frame='topbot'">
            <xsl:attribute name="style">
              <xsl:text>border-collapse: collapse;</xsl:text>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'top'"/>
              </xsl:call-template>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'bottom'"/>
              </xsl:call-template>
            </xsl:attribute>
          </xsl:when>
          <xsl:when test="../@frame='top'">
            <xsl:attribute name="style">
              <xsl:text>border-collapse: collapse;</xsl:text>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'top'"/>
              </xsl:call-template>
            </xsl:attribute>
          </xsl:when>
          <xsl:when test="../@frame='bottom'">
            <xsl:attribute name="style">
              <xsl:text>border-collapse: collapse;</xsl:text>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'bottom'"/>
              </xsl:call-template>
            </xsl:attribute>
          </xsl:when>
          <xsl:when test="../@frame='sides'">
            <xsl:attribute name="style">
              <xsl:text>border-collapse: collapse;</xsl:text>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'left'"/>
              </xsl:call-template>
              <xsl:call-template name="border">
                <xsl:with-param name="side" select="'right'"/>
              </xsl:call-template>
            </xsl:attribute>
          </xsl:when>
          <xsl:otherwise>
            <xsl:attribute name="style">
              <xsl:text>border-collapse: collapse;</xsl:text>
            </xsl:attribute>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:when test="../@frame='none'">
        <xsl:attribute name="border">0</xsl:attribute>
      </xsl:when>
      <xsl:otherwise>
        <xsl:attribute name="border">1</xsl:attribute>
      </xsl:otherwise>
    </xsl:choose>

    <xsl:variable name="colgroup">
      <colgroup>
        <xsl:call-template name="generate.colgroup">
          <xsl:with-param name="cols" select="@cols"/>
        </xsl:call-template>
      </colgroup>
    </xsl:variable>

    <xsl:variable name="explicit.table.width">
      <xsl:call-template name="dbhtml-attribute">
        <xsl:with-param name="pis"
                        select="../processing-instruction('dbhtml')[1]"/>
        <xsl:with-param name="attribute" select="'table-width'"/>
      </xsl:call-template>
    </xsl:variable>

    <xsl:variable name="table.width">
      <xsl:choose>
        <xsl:when test="$explicit.table.width != ''">
          <xsl:value-of select="$explicit.table.width"/>
        </xsl:when>
        <xsl:when test="$default.table.width = ''">
          <xsl:text>100%</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$default.table.width"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:if test="$default.table.width != ''
                  or $explicit.table.width != ''">
      <xsl:attribute name="width">
        <xsl:choose>
          <xsl:when test="contains($table.width, '%')">
            <xsl:value-of select="$table.width"/>
          </xsl:when>
          <xsl:when test="$use.extensions != 0
                          and $tablecolumns.extension != 0">
            <xsl:choose>
              <xsl:when test="function-available('stbl:convertLength')">
                <xsl:value-of select="stbl:convertLength($table.width)"/>
              </xsl:when>
              <xsl:when test="function-available('xtbl:convertLength')">
                <xsl:value-of select="xtbl:convertLength($table.width)"/>
              </xsl:when>
              <xsl:otherwise>
                <xsl:message terminate="yes">
                  <xsl:text>No convertLength function available.</xsl:text>
                </xsl:message>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$table.width"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
    </xsl:if>

    <xsl:choose>
      <xsl:when test="$use.extensions != 0
                      and $tablecolumns.extension != 0">
        <xsl:choose>
          <xsl:when test="function-available('stbl:adjustColumnWidths')">
            <xsl:copy-of select="stbl:adjustColumnWidths($colgroup)"/>
          </xsl:when>
          <xsl:when test="function-available('xtbl:adjustColumnWidths')">
            <xsl:copy-of select="xtbl:adjustColumnWidths($colgroup)"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:message terminate="yes">
              <xsl:text>No adjustColumnWidths function available.</xsl:text>
            </xsl:message>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:copy-of select="$colgroup"/>
      </xsl:otherwise>
    </xsl:choose>

    <xsl:apply-templates select="thead"/>
    <xsl:apply-templates select="tfoot"/>
    <xsl:apply-templates select="tbody"/>

    <xsl:if test=".//footnote">
      <tbody class="footnotes">
        <tr>
          <td colspan="{@cols}">
            <xsl:apply-templates select=".//footnote" mode="table.footnote.mode"/>
          </td>
        </tr>
      </tbody>
    </xsl:if>
  </table>
</xsl:template>


<xsl:template match="thead|tfoot">
  <xsl:element name="{name(.)}">
    <xsl:if test="@align">
      <xsl:attribute name="align">
        <xsl:value-of select="@align"/>
      </xsl:attribute>
    </xsl:if>
    <xsl:if test="@char">
      <xsl:attribute name="char">
        <xsl:value-of select="@char"/>
      </xsl:attribute>
    </xsl:if>
    <xsl:if test="@charoff">
      <xsl:attribute name="charoff">
        <xsl:value-of select="@charoff"/>
      </xsl:attribute>
    </xsl:if>
    <xsl:if test="@valign">
      <xsl:attribute name="valign">
        <xsl:value-of select="@valign"/>
      </xsl:attribute>
    </xsl:if>
    <!-- CX-SBT: Add a grey background for the header -->
    <xsl:attribute name="bgcolor">#EEEEEE</xsl:attribute>

    <xsl:apply-templates select="row[1]">
      <xsl:with-param name="spans">
        <xsl:call-template name="blank.spans">
          <xsl:with-param name="cols" select="../@cols"/>
        </xsl:call-template>
      </xsl:with-param>
    </xsl:apply-templates>

  </xsl:element>
</xsl:template>

</xsl:stylesheet>

