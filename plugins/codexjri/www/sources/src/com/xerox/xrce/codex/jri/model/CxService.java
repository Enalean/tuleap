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
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

/**
 * CxService is the abstract class for CodeX services provided in the CodeXJRI
 * (for the moment, only Tracker service is available). Services are provided by
 * a project (CxGroup) and can be different from a group to another. CodeX
 * services can be for instance:
 * <ul>
 * <li>Trackers</li>
 * <li>Docman (document manager)</li>
 * <li>FRS (File Release System)</li>
 * <li>...</li>
 * </ul>
 * 
 * @param <T> generic type of the objects managed by this service (e.g:
 *        CxTracker for tracker service, Item for docman service)
 */
public abstract class CxService<T> extends CxFromServer implements ITooltipable {

    /**
     * The project that provide this service
     */
    private CxGroup group;

    /**
     * Name of this service
     */
    private String name;

    /**
     * Content of this service. The content depends of the service type. For
     * Tracker service, the content is trackers. For Docman service, the content
     * would be 'Items', etc.
     */
    private Map<Integer, T> content;

    /**
     * Constructor of CxService
     * 
     * @param server the CxService belong to
     * @param name the name of the service
     */
    public CxService(CxServer server, String name) {
        super(server);
        this.name = name;
        content = new TreeMap<Integer, T>();
    }

    /**
     * Returns the objects contained in this service
     * 
     * @return the list of the object (of type <T>) contained in this service
     */
    public List<T> getContentList() {
        List<T> tList = new ArrayList<T>();
        for (T group : this.content.values()) {
            tList.add(group);
        }
        return tList;
    }

    /**
     * Returns the objects contained in this service
     * 
     * @return the map of the object (of type <T>) contained in this service
     */
    public Map<Integer, T> getContent() {
        return this.content;
    }

    /**
     * Sets the content of this service.
     * 
     * @param content a map that contains objects of type <T>
     */
    public void setContent(Map<Integer, T> content) {
        this.content = content;
    }

    /**
     * Returns the project ({@link CxGroup}) this service belong to
     * 
     * @return the project ({@link CxGroup}) this service belong to
     */
    public CxGroup getGroup() {
        return group;
    }

    /**
     * Sets the project ({@link CxGroup}) this service belong to
     * 
     * @param group the group this service belong to
     */
    public void setGroup(CxGroup group) {
        this.group = group;
    }

    /**
     * Returns the name of this service
     * 
     * @return the name of this service
     */
    public String getName() {
        return name;
    }

    /**
     * Sets the name of this service
     * 
     * @param name the name of this service
     */
    public void setName(String name) {
        this.name = name;
    }

}
