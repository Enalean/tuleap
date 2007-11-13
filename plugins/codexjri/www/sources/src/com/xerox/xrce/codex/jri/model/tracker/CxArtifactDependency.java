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
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactDependency;

/**
 * CxArtifactDependency is the class for artifact dependency.
 * 
 */
public class CxArtifactDependency extends CxFromServer {

    /**
     * ID of the artifact target of the dependency
     */
    private int dependantOnArtifactId;

    /**
     * ID of the artifact source of the dependency
     */
    private int artifactId;

    /**
     * Summary of the artifact target of the dependency (could be calculated
     * with artifact ID but duplicated for performance reason)
     */
    private String summary;

    /**
     * Name of the tracker the artifact target of the dependency belongs to
     * (could be calculated with artifact ID but duplicated for performance
     * reason)
     */
    private String trackerName;

    /**
     * Name of the group the artifact target of the dependency belongs to (could
     * be calculated with artifact ID but duplicated for performance reason)
     */
    private String groupName;

    /**
     * ID of the group the artifact target of the dependency belongs to (could
     * be calculated with artifact ID but duplicated for performance reason)
     */
    private int groupId;

    /**
     * ID of the tracker the artifact target of the dependency belongs to (could
     * be calculated with artifact ID but duplicated for performance reason)
     */
    private int trackerId;

    /**
     * Constructor from an ArtifactDependency Object
     * 
     * @param server the server this artifact dependency belongs to
     * @param artifactDependency the ArtifactDependency Object
     */
    public CxArtifactDependency(CxServer server,
            ArtifactDependency artifactDependency) {
        super(server);
        this.dependantOnArtifactId = artifactDependency.getIs_dependent_on_artifact_id();
        this.artifactId = artifactDependency.getArtifact_id();
        this.summary = artifactDependency.getSummary();
        this.trackerName = artifactDependency.getTracker_name();
        this.groupName = artifactDependency.getGroup_name();
        this.trackerId = artifactDependency.getTracker_id();
        this.groupId = artifactDependency.getGroup_id();
    }

    /**
     * Constructor from data
     * 
     * @param server the server this artifact dependency belongs to
     * @param dependantOnArtifactId the ID of the artifact target of the
     *        dependency
     * @param artifactId the ID of the artifact source of the dependency
     */
    public CxArtifactDependency(CxServer server, int dependantOnArtifactId,
            int artifactId) {
        super(server);
        this.dependantOnArtifactId = dependantOnArtifactId;
        this.artifactId = artifactId;
        this.summary = "";
        this.trackerName = "";
        this.groupName = "";
    }

    /**
     * Returns the ID of the artifact target of the dependency
     * 
     * @return the ID of the artifact target of the dependency
     */
    public int getIsDependentOnArtifactID() {
        return dependantOnArtifactId;
    }

    /**
     * Returns the summary of the artifact target of the dependency
     * 
     * @return the summary of the artifact target of the dependency
     */
    public String getSummary() {
        return summary;
    }

    /**
     * Returns the name of the tracker the artifact target of the dependency
     * belongs to
     * 
     * @return the name of the tracker the artifact target of the dependency
     *         belongs to
     */
    public String getTrackerName() {
        return trackerName;
    }

    /**
     * Returns the name of the group the artifact target of the dependency
     * belongs to
     * 
     * @return the name of the group the artifact target of the dependency
     *         belongs to
     */
    public String getGroupName() {
        return groupName;
    }

    /**
     * Returns the ID of the artifact source of this dependency
     * 
     * @return the ID of the artifact source of this dependency
     */
    public int getArtifactID() {
        return artifactId;
    }

    /**
     * Returns the ID of the group the artifact target of the dependency belongs
     * to
     * 
     * @return the ID of the group the artifact target of the dependency belongs
     *         to
     */
    public int getGroupId() {
        return groupId;
    }

    /**
     * Returns the ID of the tracker the artifact target of the dependency
     * belongs to
     * 
     * @return the ID of the tracker the artifact target of the dependency
     *         belongs to
     */
    public int getTrackerId() {
        return trackerId;
    }

}
