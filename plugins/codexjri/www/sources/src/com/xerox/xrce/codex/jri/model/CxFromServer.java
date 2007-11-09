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

/**
 * CxFromServer is the abstract class that every Object that comes from a CodeX
 * server should extend. It allows you to retrieve the CxServer corresponding to
 * the Object, and to perform all the operation.
 */
public abstract class CxFromServer {

    /**
     * Data comes from this server.
     */
    protected final CxServer server;

    /**
     * Constructor
     * 
     * @param server the server the Object comes from.
     */
    protected CxFromServer(CxServer server) {
        this.server = server;
    }

    /**
     * Returns the server this Object comes from.
     * 
     * @return the CxServer this Object comes from
     */
    public CxServer getServer() {
        return this.server;
    }

}
