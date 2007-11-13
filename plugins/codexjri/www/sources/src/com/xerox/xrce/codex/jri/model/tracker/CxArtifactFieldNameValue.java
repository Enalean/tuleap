/**
 * CodeX: Breaking Down the Barriers to Source Code Sharing
 *
 * Copyright (c) Xerox Corporation, CodeX, 2007. All Rights Reserved
 *
 * This file is licensed under the CodeX Component Software License
 *
 * @author Anne Hardyau
 * @author Marc Nazarian
 */

package com.xerox.xrce.codex.jri.model.tracker;

import com.xerox.xrce.codex.jri.model.CxFromServer;
import com.xerox.xrce.codex.jri.model.CxServer;

/**
 * CxArtifactFieldNameValue is the class for field values.
 * 
 */
public class CxArtifactFieldNameValue extends CxFromServer {

    /**
     * The Id of the artifact this field value belongs to
     */
    private int artifactId;

    /**
     * The name of the field
     */
    private String fieldName;

    /**
     * The value of the field
     */
    private String fieldValue;

    /**
     * Constructor from data
     * 
     * @param server server this field value belongs to
     * @param fieldName name of the field
     * @param fieldValue value of the field
     * @param artifactId ID of the artifact this field value belongs to
     */
    public CxArtifactFieldNameValue(CxServer server, String fieldName,
            String fieldValue, int artifactId) {
        super(server);
        this.artifactId = artifactId;
        this.fieldName = fieldName;
        this.fieldValue = fieldValue;
    }

    /**
     * Returns the ID of the artifact this field value belongs to
     * 
     * @return the ID of the artifact this field value belongs to
     */
    public int getArtifactId() {
        return artifactId;
    }

    /**
     * Returns the name of the field
     * 
     * @return the name of the field
     */
    public String getFieldName() {
        return fieldName;
    }

    /**
     * Returns the value of the field
     * 
     * @return the value of the field
     */
    public String getFieldValue() {
        return fieldValue;
    }

}
