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

package com.xerox.xrce.codex.jri.model;

import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactFieldValueList;

/**
 * CxArtifactFieldValueList is the class for list of value for a field.
 * 
 */
public class CxArtifactFieldValueList extends CxFromServer {

    /**
     * The ID of this field value list
     */
    private int id;

    /**
     * The value of this field value list
     */
    private String value;

    /**
     * Constructor from ArtifactFieldValueList Object
     * 
     * @param server the server this field value list belongs to
     * @param artifactFieldValueList the ArtifactFieldValueList Object
     */
    public CxArtifactFieldValueList(CxServer server,
            ArtifactFieldValueList artifactFieldValueList) {
        super(server);
        this.id = artifactFieldValueList.getValue_id();
        this.value = artifactFieldValueList.getValue();
    }

    /**
     * Constructor from data
     * 
     * @param server the server this field value list belongs to
     * @param id the Id of this field value list
     * @param value the value of this field value list
     */
    public CxArtifactFieldValueList(CxServer server, int id, String value) {
        super(server);
        this.id = id;
        this.value = value;
    }

    /**
     * Returns the ID of this field value list
     * 
     * @return the ID of this field value list
     */
    public int getID() {
        return id;
    }

    /**
     * Returns the value of this field value list
     * 
     * @return the value of this field value list
     */
    public String getValue() {
        return value;
    }
}
