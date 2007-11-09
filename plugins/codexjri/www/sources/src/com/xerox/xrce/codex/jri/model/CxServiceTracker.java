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

import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.List;

/**
 * CxServiceTracker is an instance of CxService class. It is the service
 * Tracker.
 * 
 */
public class CxServiceTracker extends CxService<CxTracker> {

    /**
     * Constructor
     * 
     * @param server the server that host this service
     * @param name the name of this service
     */
    public CxServiceTracker(CxServer server, String name) {
        super(server, name);
    }

    /**
     * Returns the tooltip for this service
     */
    public String getToolTip() {
        return null; // no tooltip for tracker service for the moment,
        // because there is only one service provided, so we
        // don't add a hierarchy for now
    }

    @Override
    public List<CxTracker> getContentList() {
        List<CxTracker> tList = new ArrayList<CxTracker>();
        for (CxTracker group : this.getContent().values()) {
            tList.add(group);
        }
        Collections.sort(tList, new Comparator<CxTracker>() {

            public int compare(CxTracker o1, CxTracker o2) {
                return o1.getName().compareToIgnoreCase(o2.getName());
            }
        });

        return tList;
    }

}
