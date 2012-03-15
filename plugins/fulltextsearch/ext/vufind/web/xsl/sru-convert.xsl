<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:zs="http://www.loc.gov/zing/srw/"
                xmlns:marc="http://www.loc.gov/MARC21/slim">
  <xsl:output method="xml" indent="yes"/>
  <xsl:template match="/">
    <ResultSet>
      <RecordCount><xsl:value-of select="//zs:numberOfRecords"/></RecordCount>
      <xsl:call-template name="facet"/>
      <xsl:call-template name="doc"/>
    </ResultSet>
  </xsl:template> 
    
  <xsl:template name="doc">
    <xsl:for-each select="//zs:records/zs:record">
    <record>
      <id><xsl:value-of select=".//marc:controlfield[@tag=001]"/></id>

      <xsl:if test=".//marc:datafield[@tag='245']/marc:subfield[@code='h']">
        <format><xsl:value-of select=".//marc:datafield[@tag='245']/marc:subfield[@code='h']"/></format>
      </xsl:if>

      <language>
        <xsl:value-of select="substring(.//marc:controlfield[@tag=008], 36, 3)"/>
      </language>

      <isbn>
        <xsl:value-of select=".//marc:datafield[@tag='020']/marc:subfield[@code='a']"/>
      </isbn>

      <issn>
        <xsl:value-of select=".//marc:datafield[@tag='022']/marc:subfield[@code='a']"/>
      </issn>

      <xsl:choose>
        <xsl:when test=".//marc:datafield[@tag='090']">
          <callnumber>
            <xsl:value-of select=".//marc:datafield[@tag='090']/marc:subfield[@code='a']"/>
            <xsl:value-of select=".//marc:datafield[@tag='090']/marc:subfield[@code='b']"/>
          </callnumber>
        </xsl:when>
        <xsl:otherwise>
          <xsl:if test=".//marc:datafield[@tag='050']">
            <callnumber>
              <xsl:value-of select=".//marc:datafield[@tag='050']/marc:subfield[@code='a']"/>
              <xsl:value-of select=".//marc:datafield[@tag='050']/marc:subfield[@code='b']"/>
            </callnumber>
          </xsl:if>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:if test=".//marc:datafield[@tag='100']">
        <author><xsl:value-of select=".//marc:datafield[@tag='100']/marc:subfield[@code='a']"/></author>
      </xsl:if>

      <title>
        <xsl:value-of select=".//marc:datafield[@tag='245']/marc:subfield[@code='a']"/>
        <xsl:if test=".//marc:datafield[@tag='245']/marc:subfield[@code='b']">
          <xsl:text> </xsl:text>
          <xsl:value-of select=".//marc:datafield[@tag='245']/marc:subfield[@code='b']"/>
        </xsl:if>
      </title>
      
      <xsl:if test=".//datafield[@tag='260']">
        <publisher><xsl:value-of select=".//datafield[@tag='260']/subfield[@code='b']"/></publisher>
        <publishDate><xsl:value-of select=".//datafield[@tag='260']/subfield[@code='c']"/></publishDate>
      </xsl:if>

      <xsl:if test=".//datafield[@tag='440']">
        <series><xsl:value-of select=".//marc:datafield[@tag='440']/marc:subfield[@code='a']"/></series>
      </xsl:if>

      <xsl:for-each select=".//marc:datafield[@tag='856']">
        <url><xsl:value-of select="./marc:subfield[@code='u']"/></url>
      </xsl:for-each>
      
      <xsl:call-template name="holding"/>

    </record>
    </xsl:for-each>
  </xsl:template>
  
  <xsl:template name="facet">
    <Facets>
      <xsl:for-each select="//lst[@name='facet_fields']/lst">
        <Cluster>
          <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
          <xsl:for-each select="./int">
            <xsl:variable name="elem" select="../@name"/>
            <item>
              <xsl:attribute name="count"><xsl:value-of select="."/></xsl:attribute>
              <xsl:value-of select="@name"/>
            </item>
          </xsl:for-each>
        </Cluster>
      </xsl:for-each>
    </Facets>
  </xsl:template>
  
  <xsl:template name="holding">
    <xsl:for-each select=".//marc:datafield[@tag='952']">
    <holdings>
      <withdrawn><xsl:value-of select="./marc:subfield[@code='0']"/></withdrawn>
      <itemlost><xsl:value-of select="./marc:subfield[@code='1']"/></itemlost>
      <cn_source><xsl:value-of select="./marc:subfield[@code='2']"/></cn_source>
      <materials><xsl:value-of select="./marc:subfield[@code='3']"/></materials>
      <damaged><xsl:value-of select="./marc:subfield[@code='4']"/></damaged>
      <restricted><xsl:value-of select="./marc:subfield[@code='5']"/></restricted>
      <cn_sort><xsl:value-of select="./marc:subfield[@code='6']"/></cn_sort>
      <notforloan><xsl:value-of select="./marc:subfield[@code='7']"/></notforloan>
      <ccode><xsl:value-of select="./marc:subfield[@code='8']"/></ccode>
      <itemnumber><xsl:value-of select="./marc:subfield[@code='9']"/></itemnumber>
      <homebranch><xsl:value-of select="./marc:subfield[@code='a']"/></homebranch>
      <holdingbranch><xsl:value-of select="./marc:subfield[@code='b']"/></holdingbranch>
      <location><xsl:value-of select="./marc:subfield[@code='c']"/></location>
      <dateaccessioned><xsl:value-of select="./marc:subfield[@code='d']"/></dateaccessioned>
      <booksellerid><xsl:value-of select="./marc:subfield[@code='e']"/></booksellerid>
      <coded_location_qualifier><xsl:value-of select="./marc:subfield[@code='f']"/></coded_location_qualifier>
      <price><xsl:value-of select="./marc:subfield[@code='g']"/></price>
      <renewals><xsl:value-of select="./marc:subfield[@code='j']"/></renewals>
      <stack><xsl:value-of select="./marc:subfield[@code='j']"/></stack>
      <issues><xsl:value-of select="./marc:subfield[@code='l']"/></issues>
      <renewals><xsl:value-of select="./marc:subfield[@code='m']"/></renewals>
      <reserves><xsl:value-of select="./marc:subfield[@code='n']"/></reserves>
      <itemcallnumber><xsl:value-of select="./marc:subfield[@code='o']"/></itemcallnumber>
      <barcode><xsl:value-of select="./marc:subfield[@code='p']"/></barcode>
      <onloan><xsl:value-of select="./marc:subfield[@code='q']"/></onloan>
      <datelastseen><xsl:value-of select="./marc:subfield[@code='r']"/></datelastseen>
      <datelastborrowed><xsl:value-of select="./marc:subfield[@code='s']"/></datelastborrowed>
      <copynumber><xsl:value-of select="./marc:subfield[@code='t']"/></copynumber>
      <uri><xsl:value-of select="./marc:subfield[@code='u']"/></uri>
      <replacementprice><xsl:value-of select="./marc:subfield[@code='v']"/></replacementprice>
      <replacementpricedate><xsl:value-of select="./marc:subfield[@code='w']"/></replacementpricedate>
      <paidfor><xsl:value-of select="./marc:subfield[@code='x']"/></paidfor>
      <itype><xsl:value-of select="./marc:subfield[@code='y']"/></itype>
      <itemnotes><xsl:value-of select="./marc:subfield[@code='z']"/></itemnotes>
    </holdings>
    </xsl:for-each>
  </xsl:template>
</xsl:stylesheet>