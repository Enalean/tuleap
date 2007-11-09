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

import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactCanned;

/**
 * CxArtifactCannedResponse is the class for canned response. A canned response
 * is a predefined response for follow-up comments of artifacts.
 * 
 */
public class CxArtifactCannedResponse extends CxFromServer {

    /**
     * Title of the canned response
     */
    private String title;

    /**
     * Content (message) of the canned response
     */
    private String body;

    /**
     * ID of the canned response
     */
    private int id;

    /**
     * Constructor from ArtifactCanned Object
     * 
     * @param server the server the canned response belongs to
     * @param artifactCanned the canned reponse Object
     */
    public CxArtifactCannedResponse(CxServer server,
            ArtifactCanned artifactCanned) {
        super(server);
        this.title = artifactCanned.getTitle();
        this.body = artifactCanned.getBody();
        this.id = artifactCanned.getArtifact_canned_id();
    }

    /**
     * Constructor from data
     * 
     * @param server the server the canned response belongs to
     * @param id the ID of the canned response
     * @param title the title of the canned response
     * @param body the content of the canned response
     */
    public CxArtifactCannedResponse(CxServer server, int id, String title,
            String body) {
        super(server);
        this.title = title;
        this.body = body;
        this.id = id;
    }

    /**
     * Returns the ID of the canned response
     * 
     * @return the ID of the canned response
     */
    public int getId() {
        return this.id;
    }

    /**
     * Returns the title of the canned response
     * 
     * @return the title of the canned response
     */
    public String getTitle() {
        return this.title;
    }

    /**
     * Returns the body of the canned response
     * 
     * @return the body (the message) of the canned response
     */
    public String getBody() {
        return this.body;
    }

}
