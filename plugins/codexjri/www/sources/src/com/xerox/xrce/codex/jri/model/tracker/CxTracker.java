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

import java.rmi.RemoteException;
import java.text.ChoiceFormat;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import org.apache.axis.AxisFault;

import com.xerox.xrce.codex.jri.exceptions.CxException;
import com.xerox.xrce.codex.jri.exceptions.CxRemoteException;
import com.xerox.xrce.codex.jri.exceptions.CxServerException;
import com.xerox.xrce.codex.jri.messages.JRIMessages;
import com.xerox.xrce.codex.jri.model.CxFromServer;
import com.xerox.xrce.codex.jri.model.CxGroup;
import com.xerox.xrce.codex.jri.model.CxServer;
import com.xerox.xrce.codex.jri.model.CxServiceTracker;
import com.xerox.xrce.codex.jri.model.ITooltipable;
import com.xerox.xrce.codex.jri.model.wsproxy.Artifact;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactCanned;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactFieldNameValue;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactFieldSet;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactFieldValue;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactQueryResult;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactReport;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactReportDesc;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactRule;
import com.xerox.xrce.codex.jri.model.wsproxy.ArtifactType;
import com.xerox.xrce.codex.jri.model.wsproxy.Criteria;
import com.xerox.xrce.codex.jri.model.wsproxy.TrackerDesc;

/**
 * CxTracker is the class for CodeX Trackers. A CxTracker is a generic object
 * for tracking things (like bugs, tasks, support request, requirements, etc.)
 * The generic objects tracked by the tracker is called artefact (CxArtefact).
 * 
 */
public class CxTracker extends CxFromServer implements ITooltipable {

    /**
     * CodeX service of this object
     */
    private CxServiceTracker serviceTracker;

    /**
     * Fieldsets of this tracker (tracker structure)
     */
    private List<CxArtifactFieldSet> fieldSets = null;

    /**
     * Reports of this tracker (tracker structure)
     */
    private List<CxArtifactReport> reports = null;

    /**
     * Reports description of this tracker (tracker structure). Description does
     * not contain all the report structure, but a light description, used for
     * enhance performance
     */
    private List<CxArtifactReportDesc> reportsDesc = null;

    /**
     * Artifacts of this tracker (tracker data). The map associates the artifact
     * ID to the artifact object.
     */
    private Map<Integer, CxArtifact> artifacts = null;

    /**
     * Canned responses of this tracker (tracker structure)
     */
    private List<CxArtifactCannedResponse> cannedResponses = null;

    /**
     * List of criterias used to query this tracker.
     */
    private List<CxCriteria> criterias = null;

    /**
     * Field dependencies rules of this tracker (tracker structure)
     */
    private List<CxArtifactFieldDependenciesRule> fieldDependenciesRules = null;

    /**
     * ID of this tracker
     */
    private int groupArtifactID;

    /**
     * Project (Group) this tracker belongs to
     */
    private CxGroup group;

    /**
     * Name of this tracker (label)
     */
    private String name;

    /**
     * Description of this tracker
     */
    private String description;

    /**
     * Short name of this tracker
     */
    private String itemName;

    /**
     * Number of opened artifact of this tracker
     */
    private int openCount;

    /**
     * Number total of artifact of this tracker
     */
    private int totalCount;

    /**
     * Sum of attached file size of this tracker (statistics)
     */
    private float totalFileSize;

    /**
     * Id of the selected report
     */
    private int reportIdSelected = 0;

    /**
     * Number of artifact to display on each result page (used for pagination).
     * This number can be customized in preference page.
     */
    private int numberRowToDisplay = 50;

    /**
     * Number total of artifact returned by the current query (used for
     * pagination).
     */
    private int numberRowTotal = 0;

    /**
     * Offset of displayed returned artifact (used for pagination)
     */
    private int offset = 1;

    /**
     * Id of this tracker
     */
    private int id;

    /**
     * Constructor of CxTracker from a TrackerDesc. Warning: a trackerDesc does
     * not contain all the attributes of a tracker. This a light object used for
     * performance reasons.
     * 
     * @param server the CxServer this tracker belong to
     * @param tracker the TrackerDesc to build this CxTracker
     */
    public CxTracker(CxServer server, TrackerDesc tracker) {
        super(server);
        this.groupArtifactID = tracker.getGroup_artifact_id();
        this.name = tracker.getName();
        this.description = tracker.getDescription();
        this.itemName = tracker.getItem_name();
        this.openCount = tracker.getOpen_count();
        this.totalCount = tracker.getTotal_count();
        this.id = tracker.getGroup_artifact_id();
        this.reportsDesc = new ArrayList<CxArtifactReportDesc>();
        for (ArtifactReportDesc reportDesc : tracker.getReports_desc()) {
            this.reportsDesc.add(new CxArtifactReportDesc(server, reportDesc));
        }
    }

    /**
     * Constructor of CxTracker from an ArtifactType (a Tracker).
     * 
     * @param server the {@link CxServer} this tracker belong to
     * @param tracker the ArtifactType (tracker structure) to build this
     *        CxTracker
     */
    public CxTracker(CxServer server, ArtifactType tracker) {
        super(server);
        this.groupArtifactID = tracker.getGroup_artifact_id();
        this.name = tracker.getName();
        this.description = tracker.getDescription();
        this.itemName = tracker.getItem_name();
        this.openCount = tracker.getOpen_count();
        this.totalCount = tracker.getTotal_count();
        this.id = tracker.getGroup_artifact_id();
    }

    /**
     * Returns the service this object belongs to
     * 
     * @return the service ({@link CxServiceTracker}) this object belongs to
     */
    public CxServiceTracker getServiceTracker() {
        return serviceTracker;
    }

    /**
     * Sets the service this object belongs to
     * 
     * @param serviceTracker the service ({@link CxServiceTracker}) this
     *        object belongs to
     */
    public void setServiceTracker(CxServiceTracker serviceTracker) {
        this.serviceTracker = serviceTracker;
    }

    /**
     * Returns the tooltip of this tracker. Tooltip of trackers lookslike
     * <em>tracker_name (number of opened artifacts / number of total artifacts)</em>
     * 
     * @return the tooltip of this tracker
     */
    public String getToolTip() {
        String nbCountArt = "";
        if (this.getOpenCount() != -1 && this.getTotalCount() != -1) {
            double[] filelimits = { 0, 2 };
            String[] filepart = { JRIMessages.getString("CxTracker.open"),
                                 JRIMessages.getString("CxTracker.opens") };
            ChoiceFormat fileform = new ChoiceFormat(filelimits, filepart);
            nbCountArt += JRIMessages.getString("CxTracker.artifact_count",
                fileform, this.getOpenCount(), this.getTotalCount());

        }
        return this.description + " " + nbCountArt;
    }

    /**
     * Returns the reports of this tracker.
     * 
     * @param refresh true if you want to enforce to refresh the list from the
     *        CodeX server (some changes can have be done by other users), false
     *        otherwise
     * @return the list of reports of this tracker
     * @throws CxException
     */
    public synchronized List<CxArtifactReport> getReports(boolean refresh)
                                                                          throws CxException {
        if (this.reports == null || refresh) {
            this.initReport();
        }
        return reports;
    }

    /**
     * Returns the report reportID of this tracker
     * 
     * @param reportID the ID of the returned report
     * @return the report of ID reportID, or null if no report is found
     * @throws CxException
     */
    public CxArtifactReport getReport(int reportID) throws CxException {
        List<CxArtifactReport> reports = getReports(false); // will make a soap
        // request if null
        for (CxArtifactReport currentReport : reports) {
            if (currentReport.getID() == reportID) {
                return currentReport;
            }
        }
        return null;
    }

    /**
     * Returns the reports description of this tracker. Report description are
     * light objects that describe the report, but does not contains all the
     * attribute of it.
     * 
     * @return the reports description of this tracker
     */
    public List<CxArtifactReportDesc> getReportsDesc() {
        return reportsDesc;
    }

    /**
     * Returns the artifact of this tracker.
     * 
     * @param refresh true if you want to enforce to refresh the list from the
     *        CodeX server (some changes can have be done by other users), false
     *        otherwise
     * @return the list of artifacts of this tracker
     * @throws CxException
     */
    public synchronized List<CxArtifact> getArtifacts(boolean refresh)
                                                                      throws CxException {
        if (this.artifacts == null || refresh) {
            this.initArtifacts();
        }
        List<CxArtifact> artifacts = new ArrayList<CxArtifact>();
        for (CxArtifact artifact : this.artifacts.values()) {
            artifacts.add(artifact);
        }
        Collections.sort(artifacts, new Comparator<CxArtifact>() {

            public int compare(CxArtifact o1, CxArtifact o2) {
                return new Integer(o1.getId()).compareTo(o2.getId());
            }

        });
        return artifacts;
    }

    /**
     * Returns the description of this tracker.
     * 
     * @return the description of this tracker
     */
    public String getDescription() {
        return description;
    }

    /**
     * Returns the fieldsets of this tracker.
     * 
     * @param refresh true if you want to enforce to refresh the list from the
     *        CodeX server (some changes can have be done by other users), false
     *        otherwise
     * @return the list of {@link CxArtifactFieldSet} of this tracker
     * @throws CxException
     */
    public synchronized List<CxArtifactFieldSet> getFieldSets(boolean refresh)
                                                                              throws CxException {
        if (this.fieldSets == null || refresh) {
            this.initFieldSets();
        }
        return fieldSets;
    }

    /**
     * Sets the fieldsets of this tracker
     * 
     * @param fieldSets the list of fieldsets for this tracker
     */
    public void setFieldSets(List<CxArtifactFieldSet> fieldSets) {
        this.fieldSets = fieldSets;
    }

    /**
     * Returns the ID of this tracker
     * 
     * @return the ID of this tracker
     */
    public int getGroupArtifactID() {
        return this.groupArtifactID;
    }

    /**
     * Returns the project ({@link CxGroup} this tracker belongs to
     * 
     * @return the {@link CxGroup} this tracker belongs to
     */
    public CxGroup getGroup() {
        return group;
    }

    /**
     * Sets the project ({@link CxGroup} this tracker belongs to
     * 
     * @param group the {@link CxGroup} this tracker belongs to
     */
    public void setGroup(CxGroup group) {
        this.group = group;
    }

    /**
     * Returns the short name of this tracker
     * 
     * @return the short name of this trackers
     */
    public String getItemName() {
        return this.itemName;
    }

    /**
     * Returns the name (label) of this tracker
     * 
     * @return the name (label) of this tracker
     */
    public String getName() {
        return this.name;
    }

    /**
     * Returns the ID of this tracker
     * 
     * @return the ID of this tracker
     */
    public int getId() {
        return this.id;
    }

    /**
     * Returns the number of opened artifacts of this tracker
     * 
     * @return the number of opened artifacts of this tracker, or -1 if the user
     *         is not allowed to see this number
     */
    public int getOpenCount() {
        return this.openCount;
    }

    /**
     * Returns the number of total artifacts of this tracker
     * 
     * @return the number of total artifacts of this tracker, or -1 if the user
     *         is not allowed to see this number
     */
    public int getTotalCount() {
        return this.totalCount;
    }

    public float getTotalFileSize() {
        return this.totalFileSize;
    }

    /**
     * Returns the ID of the report currently selected
     * 
     * @return the ID of the report currently selected, or 0 if no report is
     *         selected
     */
    public int getReportIdSelected() {
        return this.reportIdSelected;
    }

    /**
     * Sets the ID of the selected report
     * 
     * @param reportIdSelected the ID of the selected report
     */
    public void setReportIdSelected(int reportIdSelected) {
        this.reportIdSelected = reportIdSelected;
    }

    public int getOffset() {
        return offset;
    }

    public void setOffset(int offset) {
        this.offset = offset;
    }

    /**
     * Returns the canned responses of this tracker
     * 
     * @param refresh true if you want to enforce to refresh the list from the
     *        CodeX server (some changes can have be done by other users), false
     *        otherwise
     * @return the list of {@link CxArtifactCannedResponse} of this tracker
     * @throws CxException
     */
    public synchronized List<CxArtifactCannedResponse> getCannedResponses(
                                                                          boolean refresh)
                                                                                          throws CxException {
        if (this.cannedResponses == null || refresh) {
            this.initCannedResponses();
        }
        return this.cannedResponses;
    }

    /**
     * Sets the tracker filter for artifact search query
     * 
     * @param criterias the list of {@link CxCriteria}
     */
    public void setCriterias(List<CxCriteria> criterias) {
        this.criterias = criterias;
    }

    /**
     * Returns the number of rows to display (used for query response
     * pagination)
     * 
     * @return the number of rows to display
     */
    public int getNumberRowToDisplay() {
        return numberRowToDisplay;
    }

    /**
     * Sets the number of rows to display (used for query response pagination)
     * 
     * @param numberRowToDisplay the number of rows to display
     */
    public void setNumberRowToDisplay(int numberRowToDisplay) {
        this.numberRowToDisplay = numberRowToDisplay;
    }

    /**
     * Returns the total number of rows (used for query response pagination)
     * 
     * @return the total number of rows
     */
    public int getNumberRowTotal() {
        return numberRowTotal;
    }

    /**
     * Returns the field dependencies rules of this tracker.
     * 
     * @param refresh true if you want to enforce to refresh the list from the
     *        CodeX server (some changes can have be done by other users), false
     *        otherwise
     * @return the list of {@link CxArtifactFieldDependenciesRule} of this
     *         tracker
     * @throws CxException
     */
    public synchronized List<CxArtifactFieldDependenciesRule> getFieldDependenciesRules(
                                                                                        boolean refresh)
                                                                                                        throws CxException {
        if (this.fieldDependenciesRules == null || refresh) {
            this.initFieldDependenciesRules();
        }
        return fieldDependenciesRules;
    }

    /**
     * Returns the {@link CxArtifactField} of this tracker with ID fieldID
     * 
     * @param fieldID the ID of the field we want to retrieve
     * @return the {@link CxArtifactField} of this tracker with ID fieldID, or
     *         null if no field with fieldID ID is found
     * @throws CxException
     */
    public CxArtifactField getField(int fieldID) throws CxException {
        for (CxArtifactFieldSet fieldSet : this.getFieldSets(false)) {
            List<CxArtifactField> fields = fieldSet.getFields(false);
            for (CxArtifactField field : fields) {
                if (field.getID() == fieldID) {
                    return field;
                }
            }
        }
        return null;
    }

    /**
     * Returns the {@link CxArtifactField} of this tracker with fieldname
     * fieldName
     * 
     * @param fieldName the name of the field we want to retrieve
     * @return the {@link CxArtifactField} of this tracker with name fieldName,
     *         or null if no field with name fieldName is found
     * @throws CxException
     */
    public CxArtifactField getField(String fieldName) throws CxException {
        for (CxArtifactFieldSet fieldSet : this.getFieldSets(false)) {
            List<CxArtifactField> fields = fieldSet.getFields(false);
            for (CxArtifactField field : fields) {
                if (fieldName.equals(field.getName())) {
                    return field;
                }
            }
        }
        return null;
    }

    /**
     * Returns the fields targeted by a dependence rule with source field
     * fieldId/valueId
     * 
     * @param fieldId the field ID of the source field of the dependence rule
     * @param valueId the value ID of the source field of the dependence rule
     * @return the map of targets:
     *         {@code Map(targetFieldID, List(targetValueID))}
     * @throws CxException
     */
    public Map<Integer, List<Integer>> getFieldDependenciesRulesFromSource(
                                                                           int fieldId,
                                                                           int valueId)
                                                                                       throws CxException {
        Map<Integer, List<Integer>> valuesIdByFieldId = new HashMap<Integer, List<Integer>>();
        for (CxArtifactFieldDependenciesRule rule : this.getFieldDependenciesRules(false)) {
            if (rule.getSourceFieldID() == fieldId
                && rule.getSourceValueID() == valueId) {
                if (valuesIdByFieldId.containsKey(rule.getTargetFieldID())) {
                    List<Integer> targetValueIds = valuesIdByFieldId.get(rule.getTargetFieldID());
                    targetValueIds.add(rule.getTargetValueID());
                    valuesIdByFieldId.put(rule.getTargetFieldID(),
                        targetValueIds);
                } else {
                    List<Integer> targetValueIds = new ArrayList<Integer>();
                    targetValueIds.add(rule.getTargetValueID());
                    valuesIdByFieldId.put(rule.getTargetFieldID(),
                        targetValueIds);
                }
            }
        }
        return valuesIdByFieldId;
    }

    /**
     * Returns the fields targeted by a dependence rule with source field
     * fieldId
     * 
     * @param fieldId the field ID of the source field of the dependence rule
     * @return the list of field IDs targeted by a dependence rule with source
     *            field fieldId
     * @throws CxException
     */
    public List<Integer> getTargetFieldsDependenciesFromSource(int fieldId)
                                                                           throws CxException {
        List<Integer> targetIds = new ArrayList<Integer>();
        for (CxArtifactFieldDependenciesRule rule : this.getFieldDependenciesRules(false)) {
            if (rule.getSourceFieldID() == fieldId) {
                targetIds.add(rule.getTargetFieldID());
            }
        }
        return targetIds;
    }

    /**
     * Init reports of this tracker (tracker structure)
     * 
     * @throws CxException
     */
    private void initReport() throws CxException {
        try {
            // reports init
            this.reports = new ArrayList<CxArtifactReport>();
            ArtifactReport[] artifactsReports = null;

            artifactsReports = server.getBinding().getArtifactReports(
                server.getSession().getSession_hash(), this.group.getId(),
                this.groupArtifactID, server.getSession().getUser_id());
            for (int j = 0; j < artifactsReports.length; j++) {
                reports.add(new CxArtifactReport(this.getServer(), artifactsReports[j]));
            }
        } catch (AxisFault axisFault) {
            this.reports = null;
            throw new CxServerException(axisFault);
        } catch (RemoteException re) {
            this.reports = null;
            throw new CxRemoteException(re);
        }
    }

    /**
     * Returns the {@link CxArtifact} of this tracker with ID id
     * 
     * @param id the ID of the artifact to retrieve
     * @param refresh true if you want to enforce to refresh the list from the
     *        CodeX server (some changes can have be done by other users), false
     *        otherwise
     * @return the artifact of this tracker with ID id, or null if such an
     *         artifact is not found
     * @throws CxException
     */
    public CxArtifact getArtifactById(int id, boolean refresh)
                                                              throws CxException {
        try {
            if (this.artifacts == null || !this.artifacts.containsKey(id)
                || refresh) {
                Artifact art = server.getBinding().getArtifactById(
                    server.getSession().getSession_hash(), this.group.getId(),
                    this.groupArtifactID, id);
                CxArtifact artifact = new CxArtifact(this.getServer(), art);
                if (refresh && this.artifacts != null
                    && this.artifacts.containsKey(id)) {
                    // replace the artifact in trackers.artifact Map
                    this.artifacts.put(artifact.getId(), artifact);
                }
                artifact.setTracker(this);
                return artifact;
            } else {
                return this.artifacts.get(id);
            }
        } catch (AxisFault axisFault) {
            throw new CxServerException(axisFault);
        } catch (RemoteException re) {
            throw new CxRemoteException(re);
        }
    }

    /**
     * Adds an Artifact to this tracker.
     * 
     * @param art Articact to be added.
     * @return the ID of the artifact created
     * @throws CxException
     * @throws RemoteException
     */
    public int addArtifact(CxArtifact art) throws CxException, RemoteException {
        int ret;

        ret = this.server.getBinding().addArtifact(
            this.server.getSession().getSession_hash(), this.group.getId(),
            this.groupArtifactID, art.getStatusID(), art.getCloseDate(),
            art.getSummary(), art.getDetails(), art.getSeverity(),
            art.getArtifact().getExtra_fields());

        return ret;
    }

    /**
     * Add an artifact to this tracker
     * 
     * @param status_id the status ID of the artifact
     * @param close_date the close date of the artifact (unix timestamp)
     * @param summary the summary of the artifact
     * @param details the details (original submission) of the artifact
     * @param severity the severity of the artifact
     * @param extra_fields the array of any other field of the artifact
     *        {@link CxArtifactFieldValue}.
     * @return the Artifact ID if creation is sok.
     * @throws CxException
     * @throws RemoteException
     */
    public int addArtifact(int status_id, int close_date, String summary,
                           String details, int severity,
                           CxArtifactFieldValue[] extra_fields)
                                                               throws CxException,
                                                               RemoteException {

        ArtifactFieldValue[] extFlds = null;
        int artId;

        if (extra_fields != null) {

            extFlds = new ArtifactFieldValue[extra_fields.length];
            for (int i = 0; i < extra_fields.length; i++)
                extFlds[i] = new ArtifactFieldValue(extra_fields[i].getFieldID(), 0,
                // @todo @okb Vérifier que artifact_id = 0 est Ok pour une
                // création.
                extra_fields[i].getFieldValue());

        }

        artId = this.server.getBinding().addArtifact(
            this.server.getSession().getSession_hash(), this.group.getId(),
            this.groupArtifactID, status_id, close_date, summary, details,
            severity, extFlds);

        return artId;
    }

    /**
     * Add an artifact to this tracker
     * 
     * @param statusId the status Id of the artifact
     * @param closeDate the close date of the artifact (unix timestamp)
     * @param summary the summary of the artifact
     * @param details the details (original submission) of the artifact
     * @param severity the severity of the artifact
     * @param fields any other field of the artifact.
     *        {@link CxArtifactFieldNameValue}
     * @return the ID of the created artifact
     * @throws CxException
     */
    public int addArtifactByValuesNames(int statusId, int closeDate,
                                        String summary, String details,
                                        int severity,
                                        List<CxArtifactFieldNameValue> fields)
                                                                              throws CxException {
        int retval = -1;
        try {
            ArtifactFieldNameValue[] fieldsArray = new ArtifactFieldNameValue[fields.size()];
            int i = 0;
            for (CxArtifactFieldNameValue field : fields) {
                fieldsArray[i] = new ArtifactFieldNameValue(field.getFieldName(), field.getArtifactId(), field.getFieldValue());
                i++;
            }
            retval = server.getBinding().addArtifactWithFieldNames(
                server.getSession().getSession_hash(), this.getGroup().getId(),
                this.getGroupArtifactID(), statusId, closeDate, summary,
                details, severity, fieldsArray);
        } catch (AxisFault axisFault) {
            // TODO : treat the exception
            throw new CxServerException(axisFault);
        } catch (RemoteException re) {
            throw new CxRemoteException(re);
        }
        return retval;

    }

    /**
     * Init artifact of this tracker (tracker data).
     * 
     * @throws CxException
     */
    private void initArtifacts() throws CxException {
        try {
            // artifacts init
            this.artifacts = new TreeMap<Integer, CxArtifact>();
            ArtifactQueryResult artifactQueryResult = null;
            Artifact[] artifacts = null;

            Criteria[] criteriasQuery = null;
            if (this.criterias != null && this.criterias.size() != 0) {
                criteriasQuery = new Criteria[this.criterias.size()];
                // List<Criteria> criterias = new ArrayList<Criteria>();
                int i = 0;
                for (CxCriteria cxCriteria : this.criterias) {
                    Criteria criteria = new Criteria(cxCriteria.getFieldName(), cxCriteria.getFieldValue(), cxCriteria.getOperator());
                    // criterias.add(criteria);
                    criteriasQuery[i] = criteria;
                    i++;
                }
                // criteriasQuery = (Criteria[])criterias.toArray();
            }

            artifactQueryResult = server.getBinding().getArtifacts(
                server.getSession().getSession_hash(), this.group.getId(),
                this.groupArtifactID, criteriasQuery, offset - 1,
                numberRowToDisplay);

            artifacts = artifactQueryResult.getArtifacts();
            this.numberRowTotal = artifactQueryResult.getTotal_artifacts_number();

            if (artifacts != null) {
                for (int j = 0; j < artifacts.length; j++) {
                    CxArtifact artifact = new CxArtifact(this.server, artifacts[j]);
                    artifact.setTracker(this);
                    this.artifacts.put(artifact.getId(), artifact);
                }
            }
        } catch (AxisFault axisFault) {
            this.artifacts = null;
            throw new CxServerException(axisFault);
        } catch (RemoteException re) {
            this.artifacts = null;
            throw new CxRemoteException(re);
        }
    }

    /**
     * Init fieldsets of this tracker (tracker structure).
     * 
     * @throws CxException
     */
    private void initFieldSets() throws CxException {
        try {
            // fieldsets init
            this.fieldSets = new ArrayList<CxArtifactFieldSet>();
            ArtifactFieldSet[] artifactsFieldSets = null;

            artifactsFieldSets = server.getBinding().getArtifactType(
                server.getSession().getSession_hash(), this.group.getId(),
                this.groupArtifactID).getField_sets();

            for (int j = 0; j < artifactsFieldSets.length; j++) {
                CxArtifactFieldSet fieldSet = new CxArtifactFieldSet(this.getServer(), artifactsFieldSets[j]);
                fieldSet.setTracker(this);
                this.fieldSets.add(fieldSet);
            }
        } catch (AxisFault axisFault) {
            this.fieldSets = null;
            throw new CxServerException(axisFault);
        } catch (RemoteException re) {
            this.fieldSets = null;
            throw new CxRemoteException(re);
        }
    }

    /**
     * Init canned responses of this tracker (tracker structure)
     * 
     * @throws CxException
     */
    private void initCannedResponses() throws CxException {
        try {
            // canned responses init
            this.cannedResponses = new ArrayList<CxArtifactCannedResponse>();
            ArtifactCanned[] canned = null;

            canned = server.getBinding().getArtifactCannedResponses(
                server.getSession().getSession_hash(), this.group.getId(),
                this.groupArtifactID);

            for (int j = 0; j < canned.length; j++) {
                this.cannedResponses.add(new CxArtifactCannedResponse(this.getServer(), canned[j]));
            }
            // add None as canned response
            CxArtifactCannedResponse none = new CxArtifactCannedResponse(this.getServer(), 0, "None", "");
            this.cannedResponses.add(none);
        } catch (AxisFault axisFault) {
            this.cannedResponses = null;
            throw new CxServerException(axisFault);
        } catch (RemoteException re) {
            this.cannedResponses = null;
            throw new CxRemoteException(re);
        }
    }

    /**
     * Init field dependencies rules of this tracker (tracker structure)
     * 
     * @throws CxException
     */
    private void initFieldDependenciesRules() throws CxException {
        try {
            // field dependencies init
            this.fieldDependenciesRules = new ArrayList<CxArtifactFieldDependenciesRule>();
            ArtifactRule[] artifactsRules = null;

            artifactsRules = server.getBinding().getArtifactType(
                server.getSession().getSession_hash(), this.group.getId(),
                this.groupArtifactID).getField_dependencies();

            for (int j = 0; j < artifactsRules.length; j++) {
                this.fieldDependenciesRules.add(new CxArtifactFieldDependenciesRule(this.getServer(), artifactsRules[j]));
            }
        } catch (AxisFault axisFault) {
            this.fieldDependenciesRules = null;
            throw new CxServerException(axisFault);
        } catch (RemoteException re) {
            this.fieldDependenciesRules = null;
            throw new CxRemoteException(re);
        }
    }

}
