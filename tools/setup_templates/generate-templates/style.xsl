<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" version="1.0" indent="yes"/>

    <!-- This section allows to integrate the content of -->
    <!-- a XML file inside the main XML document.        -->
    <!-- This is mainly used to avoid duplication of     -->
    <!-- XML tracker definitions.                        -->
    <xsl:template match="//include-template-tracker">
        <xsl:copy-of select="document(@path)/*"/>
    </xsl:template>

    <!-- This copies everything else as is. (light!) -->
    <xsl:template match="*|@*">
        <xsl:copy>
            <xsl:apply-templates select="node()|@*"/>
        </xsl:copy>
    </xsl:template>
</xsl:stylesheet>
