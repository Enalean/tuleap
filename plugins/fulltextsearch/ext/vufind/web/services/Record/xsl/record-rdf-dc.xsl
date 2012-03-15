<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
                              xmlns:dc="http://purl.org/dc/elements/1.1/"
                              xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                              xmlns:marc="http://www.loc.gov/MARC21/slim"
                              exclude-result-prefixes="marc">
	<xsl:import href="MARC21slimUtils.xsl"/>
	<xsl:output method="xml" indent="yes"/>
	
	<xsl:template match="/">
			<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="marc:record">
		<xsl:variable name="leader" select="marc:leader"/>
		<xsl:variable name="leader6" select="substring($leader,7,1)"/>
		<xsl:variable name="leader7" select="substring($leader,8,1)"/>
		<xsl:variable name="controlField008" select="marc:controlfield[@tag=008]"/>

      <rdf:RDF>
	  <rdf:Description rdf:about="http://www.w3.org/TR/rdf-syntax-grammar">
			<xsl:for-each select="marc:datafield[@tag=245]">
				<dc:title>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abfghk</xsl:with-param>
					</xsl:call-template>
				</dc:title>
			</xsl:for-each>

	
			<xsl:for-each select="marc:datafield[@tag=100]|marc:datafield[@tag=110]|marc:datafield[@tag=111]|marc:datafield[@tag=700]|marc:datafield[@tag=710]|marc:datafield[@tag=711]|marc:datafield[@tag=720]">
				<dc:creator>
					<xsl:value-of select="."/>
				</dc:creator>
			</xsl:for-each>

			<dc:type>		
				<xsl:if test="$leader7='c'">
				<xsl:text>collection</xsl:text>
				<!--Removed attributes 6/04 jer-->
					<!--<xsl:attribute name="collection">yes</xsl:attribute>-->
				</xsl:if>

				<xsl:if test="$leader6='d' or $leader6='f' or $leader6='p' or $leader6='t'">
					<!--Removed attributes 6/04 jer-->
				
					<!--<xsl:attribute name="manuscript">yes</xsl:attribute>-->
					<xsl:text>manuscript</xsl:text>
				</xsl:if>

				<xsl:choose>
					<xsl:when test="$leader6='a' or $leader6='t'">text</xsl:when>
					<xsl:when test="$leader6='e' or $leader6='f'">cartographic</xsl:when>
					<xsl:when test="$leader6='c' or $leader6='d'">notated music</xsl:when>
					<xsl:when test="$leader6='i' or $leader6='j'">sound recording</xsl:when>
					<xsl:when test="$leader6='k'">still image</xsl:when>
					<xsl:when test="$leader6='g'">moving image</xsl:when>
					<xsl:when test="$leader6='r'">three dimensional object</xsl:when>
					<xsl:when test="$leader6='m'">software, multimedia</xsl:when>
					<xsl:when test="$leader6='p'">mixed material</xsl:when>
				</xsl:choose>
			</dc:type>

			<xsl:for-each select="marc:datafield[@tag=655]">
				<dc:type>
					<xsl:value-of select="."/>
				</dc:type>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=260]">
				<dc:publisher>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">ab</xsl:with-param>
					</xsl:call-template>
				</dc:publisher>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=260]/marc:subfield[@code='c']">
				<dc:date>
					<xsl:value-of select="."/>
				</dc:date>				
			</xsl:for-each>

			<dc:language>
				<xsl:value-of select="substring($controlField008,36,3)"/>
			</dc:language>

			<xsl:for-each select="marc:datafield[@tag=856]/marc:subfield[@code='q']">
				<dc:format>
					<xsl:value-of select="."/>
				</dc:format>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=520]">
				<dc:description>
					<xsl:value-of select="marc:subfield[@code='a']"/>
				</dc:description>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=521]">
				<dc:description>
					<xsl:value-of select="marc:subfield[@code='a']"/>
				</dc:description>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[500&lt;@tag][@tag&lt;=599][not(@tag=506 or @tag=530 or @tag=540 or @tag=546)]">
				<dc:description>
					<xsl:value-of select="marc:subfield[@code='a']"/>
				</dc:description>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=600]">
				<dc:subject>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abcdq</xsl:with-param>
					</xsl:call-template>
				</dc:subject>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=610]">
				<dc:subject>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abcdq</xsl:with-param>
					</xsl:call-template>
				</dc:subject>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=611]">
				<dc:subject>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abcdq</xsl:with-param>
					</xsl:call-template>
				</dc:subject>
			</xsl:for-each>
		
			<xsl:for-each select="marc:datafield[@tag=630]">
				<dc:subject>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abcdq</xsl:with-param>
					</xsl:call-template>
				</dc:subject>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=650]">
				<dc:subject>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abcdq</xsl:with-param>
					</xsl:call-template>
				</dc:subject>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=653]">
				<dc:subject>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abcdq</xsl:with-param>
					</xsl:call-template>
				</dc:subject>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=752]">
				<dc:coverage>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abcd</xsl:with-param>
					</xsl:call-template>
				</dc:coverage>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=530]">
				<dc:relation type="original">
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">abcdu</xsl:with-param>
					</xsl:call-template>
				</dc:relation>	
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=760]|marc:datafield[@tag=762]|marc:datafield[@tag=765]|marc:datafield[@tag=767]|marc:datafield[@tag=770]|marc:datafield[@tag=772]|marc:datafield[@tag=773]|marc:datafield[@tag=774]|marc:datafield[@tag=775]|marc:datafield[@tag=776]|marc:datafield[@tag=777]|marc:datafield[@tag=780]|marc:datafield[@tag=785]|marc:datafield[@tag=786]|marc:datafield[@tag=787]">
				<dc:relation>
					<xsl:call-template name="subfieldSelect">
						<xsl:with-param name="codes">ot</xsl:with-param>
					</xsl:call-template>
				</dc:relation>	
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=856]">
				<dc:identifier>
					<xsl:value-of select="subfield[@code='u']"/>
				</dc:identifier>
			</xsl:for-each>
			
			<xsl:for-each select="marc:datafield[@tag=020]">
			<dc:identifier>
				<xsl:text>URN:ISBN:</xsl:text>
				<xsl:value-of select="subfield[@code='a']"/>
			</dc:identifier>
		</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=506]">
				<dc:rights>
					<xsl:value-of select="subfield[@code='a']"/>
				</dc:rights>
			</xsl:for-each>

			<xsl:for-each select="marc:datafield[@tag=540]">
				<dc:rights>
					<xsl:value-of select="subfield[@code='a']"/>
				</dc:rights>
			</xsl:for-each>
		</rdf:Description>
		</rdf:RDF>
	</xsl:template>
</xsl:stylesheet>
<!-- Stylus Studio meta-information - (c)1998-2003 Copyright Sonic Software Corporation. All rights reserved.
<metaInformation>
<scenarios ><scenario default="yes" name="RDF" userelativepaths="yes" externalpreview="no" url="marcxmlfile.xml" htmlbaseurl="" outputurl="" processortype="internal" commandline="" additionalpath="" additionalclasspath="" postprocessortype="none" postprocesscommandline="" postprocessadditionalpath="" postprocessgeneratedext=""/></scenarios><MapperInfo srcSchemaPath="" srcSchemaRoot="" srcSchemaPathIsRelative="yes" srcSchemaInterpretAsXML="no" destSchemaPath="" destSchemaRoot="" destSchemaPathIsRelative="yes" destSchemaInterpretAsXML="no"/>
</metaInformation>
-->