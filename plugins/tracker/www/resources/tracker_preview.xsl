<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:t="http://codendi.org/tracker">
    <xsl:template match="/t:tracker">
        <html>
            <head>
                <title>Tracker Preview</title>
            </head>
            <body>
                <h2>Imported Tracker</h2>
                <xsl:if test="count(attribute::*) != 0">
                    <h3>Properties:</h3>
                    <ul>
                        <xsl:for-each select="attribute::*">
                            <li>
                                <xsl:value-of select="local-name()" />
                                =
                                <xsl:value-of select="." />
                            </li>
                        </xsl:for-each>
                    </ul>
                </xsl:if>
                <h3>Fields</h3>
                <ul>
                    <xsl:for-each select="t:formElements/t:formElement">
                        <xsl:call-template name="field" />
                    </xsl:for-each>
                </ul>
                <h3>Canned Responses</h3>
                <dl>
                    <xsl:for-each select="t:cannedResponses/t:cannedResponse">
                        <xsl:call-template name="response" />
                    </xsl:for-each>
                </dl>
                <h3>Report</h3>
                <h4>Criterias</h4>
                <ul>
                    <xsl:for-each select="t:report/t:criterias/t:criteria">
                        <xsl:variable name="fieldID" select="t:field/@REF" />
                        <li>
                            <xsl:value-of select="//t:formElement[@ID=$fieldID]/t:label" />
                        </li>
                    </xsl:for-each>
                </ul>
                <h4>
                    Renderers
                </h4>
                <ul>
                    <xsl:for-each select="t:report/t:renderers/t:renderer">
                        <xsl:call-template name="renderer" />
                    </xsl:for-each>
                </ul>
                <h3>Tooltip</h3>
                <ul>
                    <xsl:for-each select="t:tooltip/t:field">
                        <xsl:call-template name="fieldById">
                            <xsl:with-param name="fieldID" select="@REF" />
                        </xsl:call-template>
                    </xsl:for-each>
                </ul>
            </body>
        </html>
    </xsl:template>

    <xsl:template name="field">
        <xsl:if test="count(@use_it)=0 or @use_it=1">
            <li>
                <xsl:value-of select="t:label" />
                :
                <xsl:choose>
                    <xsl:when
                        test="@type='string' or @type='int' or @type='float' or @type='aid'">
                        <input type="text" title="{t:description}" />
                    </xsl:when>
                    <xsl:when test="@type='text'">
                        <textarea rows="{t:properties/@rows}" cols="{t:properties/@cols}" />
                    </xsl:when>
                    <xsl:when test="@type='sb' or @type='msb' or @type='tbl'">
                        <select title="{t:description}">
                            <xsl:for-each select="t:bind/t:items/t:item">
                                <option>
                                    <xsl:value-of select="@label" />
                                </option>
                            </xsl:for-each>
                        </select>
                    </xsl:when>
                    <xsl:when test="@type='date' or @type='file'">
                        <input type="text" title="{t:description}" />
                    </xsl:when>
                    <xsl:when test="@type='fieldset'">
                        Fieldset
                    </xsl:when>
                    <xsl:when test="@type='column'">
                        Column
                    </xsl:when>
                    <xsl:when test="@type='linebreak'">
                        LineBreak
                    </xsl:when>
                    <xsl:when test="@type='separator'">
                        Separator
                    </xsl:when>
                    <xsl:when test="@type='staticrichtext'">
                        Static Rich Text
                    </xsl:when>
                    <xsl:otherwise>
                        Fiedtype
                        <b>
                            <xsl:value-of select="@type" />
                        </b>
                        is unknown.
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:if test="@required=1">
                    *
                </xsl:if>
                <xsl:for-each select="t:formElements/t:formElement">
                    <ul>
                        <xsl:call-template name="field" />
                    </ul>
                </xsl:for-each>
            </li>
        </xsl:if>
    </xsl:template>

    <xsl:template name="response">
        <dt>
            <xsl:value-of select="t:title" />
        </dt>
        <dd>
            <xsl:value-of select="t:body" />
        </dd>
    </xsl:template>

    <xsl:template name="fieldById">
        <xsl:param name="fieldID" />
        <li>
            <!-- display the label of the referenced field -->
            <xsl:value-of select="//t:formElement[@ID=$fieldID]/t:label" />
        </li>
    </xsl:template>

    <xsl:template name="renderer">
        <xsl:value-of select="t:name" />
        <ul>
            <xsl:for-each select="t:columns/t:field">
                <xsl:call-template name="fieldById">
                    <xsl:with-param name="fieldID" select="@REF" />
                </xsl:call-template>
            </xsl:for-each>
        </ul>
    </xsl:template>

</xsl:stylesheet>