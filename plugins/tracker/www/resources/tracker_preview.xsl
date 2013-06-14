<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/tracker">
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
                    <xsl:for-each select="formElements/formElement">
                        <xsl:call-template name="field" />
                    </xsl:for-each>
                </ul>
                <h3>Canned Responses</h3>
                <dl>
                    <xsl:for-each select="cannedResponses/cannedResponse">
                        <xsl:call-template name="response" />
                    </xsl:for-each>
                </dl>
                <h3>Report</h3>
                <h4>Criterias</h4>
                <ul>
                    <xsl:for-each select="report/criterias/criteria">
                        <xsl:variable name="fieldID" select="field/@REF" />
                        <li>
                            <xsl:value-of select="//formElement[@ID=$fieldID]/label" />
                        </li>
                    </xsl:for-each>
                </ul>
                <h4>
                    Renderers
                </h4>
                <ul>
                    <xsl:for-each select="report/renderers/renderer">
                        <xsl:call-template name="renderer" />
                    </xsl:for-each>
                </ul>
                <h3>Tooltip</h3>
                <ul>
                    <xsl:for-each select="tooltip/field">
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
                <xsl:value-of select="label" />
                :
                <xsl:choose>
                    <xsl:when
                        test="@type='string' or @type='int' or @type='float' or @type='aid'">
                        <input type="text" title="{description}" />
                    </xsl:when>
                    <xsl:when test="@type='text'">
                        <textarea rows="{properties/@rows}" cols="{properties/@cols}" />
                    </xsl:when>
                    <xsl:when test="@type='sb' or @type='msb' or @type='tbl'">
                        <select title="{description}">
                            <xsl:for-each select="bind/items/item">
                                <option>
                                    <xsl:value-of select="@label" />
                                </option>
                            </xsl:for-each>
                        </select>
                    </xsl:when>
                    <xsl:when test="@type='date' or @type='file'">
                        <input type="text" title="{description}" />
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
                <xsl:for-each select="formElements/formElement">
                    <ul>
                        <xsl:call-template name="field" />
                    </ul>
                </xsl:for-each>
            </li>
        </xsl:if>
    </xsl:template>

    <xsl:template name="response">
        <dt>
            <xsl:value-of select="title" />
        </dt>
        <dd>
            <xsl:value-of select="body" />
        </dd>
    </xsl:template>

    <xsl:template name="fieldById">
        <xsl:param name="fieldID" />
        <li>
            <!-- display the label of the referenced field -->
            <xsl:value-of select="//formElement[@ID=$fieldID]/label" />
        </li>
    </xsl:template>

    <xsl:template name="renderer">
        <xsl:value-of select="name" />
        <ul>
            <xsl:for-each select="columns/field">
                <xsl:call-template name="fieldById">
                    <xsl:with-param name="fieldID" select="@REF" />
                </xsl:call-template>
            </xsl:for-each>
        </ul>
    </xsl:template>

</xsl:stylesheet>
