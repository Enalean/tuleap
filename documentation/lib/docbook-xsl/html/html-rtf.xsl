<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:set="http://exslt.org/sets"
                exclude-result-prefixes="exsl set"
                version="1.0">

<!-- This module contains templates that match against HTML nodes. It is used
     to post-process result tree fragments for some sorts of cleanup.
     These templates can only ever be fired by a processor that supports
     exslt:node-set(). -->

<!-- ==================================================================== -->

<!-- insert.html.p mode templates insert a particular RTF at the beginning
     of the first paragraph in the primary RTF. -->

<xsl:template match="/" mode="insert.html.p">
  <xsl:param name="mark" select="'?'"/>
  <xsl:apply-templates mode="insert.html.p">
    <xsl:with-param name="mark" select="$mark"/>
  </xsl:apply-templates>
</xsl:template>

<xsl:template match="*" mode="insert.html.p">
  <xsl:param name="mark" select="'?'"/>
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates mode="insert.html.p">
      <xsl:with-param name="mark" select="$mark"/>
    </xsl:apply-templates>
  </xsl:copy>
</xsl:template>

<xsl:template match="p" mode="insert.html.p">
  <xsl:param name="mark" select="'?'"/>
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:if test="not(preceding::p)">
      <xsl:copy-of select="$mark"/>
    </xsl:if>
    <xsl:apply-templates mode="insert.html.p">
      <xsl:with-param name="mark" select="$mark"/>
    </xsl:apply-templates>
  </xsl:copy>
</xsl:template>

<xsl:template match="text()|processing-instruction()|comment()" mode="insert.html.p">
  <xsl:param name="mark" select="'?'"/>
  <xsl:copy/>
</xsl:template>

<!-- ==================================================================== -->

<!-- insert.html.text mode templates insert a particular RTF at the beginning
     of the first text-node in the primary RTF. -->

<xsl:template match="/" mode="insert.html.text">
  <xsl:param name="mark" select="'?'"/>
  <xsl:apply-templates mode="insert.html.text">
    <xsl:with-param name="mark" select="$mark"/>
  </xsl:apply-templates>
</xsl:template>

<xsl:template match="*" mode="insert.html.text">
  <xsl:param name="mark" select="'?'"/>
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates mode="insert.html.text">
      <xsl:with-param name="mark" select="$mark"/>
    </xsl:apply-templates>
  </xsl:copy>
</xsl:template>

<xsl:template match="text()|processing-instruction()|comment()" mode="insert.html.text">
  <xsl:param name="mark" select="'?'"/>

  <xsl:if test="not(preceding::text())">
    <xsl:copy-of select="$mark"/>
  </xsl:if>

  <xsl:copy/>
</xsl:template>

<xsl:template match="processing-instruction()|comment()" mode="insert.html.text">
  <xsl:param name="mark" select="'?'"/>
  <xsl:copy/>
</xsl:template>

<!-- ==================================================================== -->

<!-- unwrap.p mode templates remove blocks from HTML p elements (and
     other places where blocks aren't allowed) -->

<xsl:template name="unwrap.p">
  <xsl:param name="p"/>
  <xsl:choose>
    <xsl:when test="function-available('exsl:node-set')
                    and function-available('set:leading')
                    and function-available('set:trailing')">
      <xsl:apply-templates select="exsl:node-set($p)" mode="unwrap.p"/>
    </xsl:when>
    <xsl:otherwise>
      <xsl:copy-of select="$p"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="p" mode="unwrap.p">
  <xsl:variable name="blocks" select="div|p|blockquote|table"/>

  <xsl:choose>
    <xsl:when test="$blocks">
      <xsl:call-template name="unwrap.nodes">
        <xsl:with-param name="wrap" select="."/>
        <xsl:with-param name="first" select="1"/>
        <xsl:with-param name="nodes" select="node()"/>
        <xsl:with-param name="blocks" select="$blocks"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <xsl:copy>
        <xsl:copy-of select="@*"/>
        <xsl:apply-templates mode="unwrap.p"/>
      </xsl:copy>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="*" mode="unwrap.p">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates mode="unwrap.p"/>
  </xsl:copy>
</xsl:template>

<xsl:template match="text()|processing-instruction()|comment()" mode="unwrap.p">
  <xsl:copy/>
</xsl:template>

<xsl:template name="unwrap.nodes">
  <xsl:param name="wrap" select="."/>
  <xsl:param name="first" select="0"/>
  <xsl:param name="nodes"/>
  <xsl:param name="blocks"/>
  <xsl:variable name="block" select="$blocks[1]"/>

  <!-- This template should never get called if these functions aren't available -->
  <!-- but this test is still necessary so that processors don't choke on the -->
  <!-- function calls if they don't support the set: functions -->
  <xsl:if test="function-available('set:leading')
                and function-available('set:trailing')">
    <xsl:choose>
      <xsl:when test="$blocks">
        <xsl:variable name="leading" select="set:leading($nodes,$blocks)"/>
        <xsl:variable name="trailing" select="set:trailing($nodes,$blocks)"/>

        <xsl:element name="{local-name($wrap)}" namespace="{namespace-uri($wrap)}">
          <xsl:for-each select="$wrap/@*">
            <xsl:if test="$first != 0 or local-name(.) != 'id'">
              <xsl:copy/>
            </xsl:if>
          </xsl:for-each>
          <xsl:apply-templates select="$leading" mode="unwrap.p"/>
        </xsl:element>

        <xsl:apply-templates select="$block" mode="unwrap.p"/>

        <xsl:if test="$trailing">
          <xsl:call-template name="unwrap.nodes">
            <xsl:with-param name="wrap" select="$wrap"/>
            <xsl:with-param name="nodes" select="$trailing"/>
            <xsl:with-param name="blocks" select="$blocks[position() &gt; 1]"/>
          </xsl:call-template>
        </xsl:if>
      </xsl:when>

      <xsl:otherwise>
        <xsl:element name="{local-name($wrap)}" namespace="{namespace-uri($wrap)}">
          <xsl:for-each select="$wrap/@*">
            <xsl:if test="$first != 0 or local-name(.) != 'id'">
              <xsl:copy/>
            </xsl:if>
          </xsl:for-each>
          <xsl:apply-templates select="$nodes" mode="unwrap.p"/>
        </xsl:element>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:if>
</xsl:template>

<!-- ==================================================================== -->

</xsl:stylesheet>
