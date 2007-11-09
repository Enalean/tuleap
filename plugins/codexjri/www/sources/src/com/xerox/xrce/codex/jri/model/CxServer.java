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

import java.beans.PropertyChangeListener;
import java.beans.PropertyChangeSupport;
import java.net.ConnectException;
import java.net.MalformedURLException;
import java.net.URL;
import java.rmi.RemoteException;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import javax.xml.rpc.ServiceException;

import org.apache.axis.AxisFault;

import com.xerox.xrce.codex.jri.exceptions.CxException;
import com.xerox.xrce.codex.jri.exceptions.CxLoginException;
import com.xerox.xrce.codex.jri.exceptions.CxRemoteException;
import com.xerox.xrce.codex.jri.exceptions.CxServerException;
import com.xerox.xrce.codex.jri.messages.JRIMessages;
import com.xerox.xrce.codex.jri.model.wsproxy.CodeXAPIBindingStub;
import com.xerox.xrce.codex.jri.model.wsproxy.CodeXAPILocator;
import com.xerox.xrce.codex.jri.model.wsproxy.Group;
import com.xerox.xrce.codex.jri.model.wsproxy.Session;

/**
 * CxServer is the class for CodeX Servers. CodeXJRI allows you to manage
 * several CodeX servers.
 */
public class CxServer {

    // --------- constants part ---------
    /**
     * Constant string for property 'state'
     */
    public static final String PR_STATE = "state";

    // --------- instance variables part ---------

    /**
     * Hostname of this server
     */
    private final String hostname;

    /**
     * Name of this server
     */
    private final String name;

    /**
     * Id of this server (internal)
     */
    private final int id;

    /**
     * To manage listeners to connection state.
     */
    private PropertyChangeSupport propertyChangeSupport = new PropertyChangeSupport(this);

    /**
     * Binding object to CodeX Server SOAP API
     */
    private CodeXAPIBindingStub binding;

    /**
     * Session for this server
     */
    private Session session;

    /**
     * Projects (Groups) hosted on this server
     */
    private Map<Integer, CxGroup> groups = null;

    // --------- constructors & init part --------

    /**
     * Constructor of CxServer
     * 
     * @param hostname URL of the CodeX server (e.g: http://codex.xerox.com)
     * @param name name of the server for your application (you can choose the
     *        name you want)
     * @param id Id of the server (application internal)
     */
    /* package */CxServer(String hostname, String name, int id) {
        this.name = name;
        this.hostname = hostname;
        this.id = id;
    }

    /**
     * Prepare the Binding Object.
     * 
     * @return the binding object
     * @throws CxException
     */
    private CodeXAPIBindingStub prepareBinding() throws CxException {
        CodeXAPIBindingStub binding = null;
        try {
            URL url = new URL(this.getHostname() + "/soap/index.php"); //$NON-NLS-1$
            binding = (CodeXAPIBindingStub) new CodeXAPILocator().getCodeXAPIPort(url);
        } catch (ServiceException jre) {
            if (jre.getLinkedCause() != null)
                jre.getLinkedCause().printStackTrace(); // TODO
        } catch (MalformedURLException mue) {
            throw new CxException(new CxException(JRIMessages.getString("CxServer.error_server_address")
                                                  + this.getHostname(), mue));
        } catch (Exception e) {
            e.printStackTrace(); // TODO
        }

        // TODO : make a ping to test if the URL is responding or not. To be
        // done here or just after creating the URL

        // Time out after a minute
        binding.setTimeout(60000);

        return binding;
    }

    /**
     * Initialize the server using a user/password
     * 
     * @param password CodeX password for the user
     * @param username CodeX username
     * @throws CxException
     */
    /* package */void init(String password, String username)
                                                             throws CxException {
        CodeXAPIBindingStub binding;

        binding = prepareBinding();

        // LOGIN
        Session session = null;
        try {
            session = binding.login(username, password);
        } catch (AxisFault axisFault) {
            if (axisFault.detail instanceof ConnectException) {
                throw new CxException(new AxisFault(this.name
                                                    + " : " + axisFault.detail.getMessage() + JRIMessages.getString("CxServer.contact_adm"))); //$NON-NLS-1$ //$NON-NLS-2$
            } else {
                throw new CxLoginException(axisFault);
            }
        } catch (RemoteException re) {
            this.session = null;
            throw new CxRemoteException(re);
        }

        this.binding = binding;
        this.session = session;

        this.propertyChangeSupport.firePropertyChange(PR_STATE, false, true);
    }

    /**
     * Initialize the server from an existing session, giving the session_hash
     * string
     * 
     * @param sessionHash The Session hash code
     * @throws CxException
     */
    /* package */void init(String sessionHash) throws CxException {
        CodeXAPIBindingStub binding;

        binding = prepareBinding();

        // LOGIN
        Session session = null;
        try {
            session = binding.retrieveSession(sessionHash);
        } catch (AxisFault axisFault) {
            if (axisFault.detail instanceof ConnectException) {
                throw new CxLoginException(new AxisFault(this.name
                                                         + " : " + axisFault.detail.getMessage() + JRIMessages.getString("CxServer.contact_adm"))); //$NON-NLS-1$ //$NON-NLS-2$
            } else {
                throw new CxLoginException(axisFault);
            }
        } catch (RemoteException re) {
            this.session = null;
            throw new CxRemoteException(re);
        }

        this.binding = binding;
        this.session = session;

        this.propertyChangeSupport.firePropertyChange(PR_STATE, false, true);

    } // init

    /**
     * Close the server connection.
     * 
     */
    public void logout() {
        if (this.binding == null || this.session == null) {
            return;
        }
        // TODO review logout
        try {
            this.binding.logout(this.session.getSession_hash());
        } catch (RemoteException e) {
            e.printStackTrace(); // TODO propage it ?
        } finally {
            this.binding = null;
            this.session = null;
            this.groups = null;
            this.propertyChangeSupport.firePropertyChange(PR_STATE, true, false);
        }
    }

    // --------- accessing part ---------

    /**
     * Returns the name of this server
     * 
     * @return the name of this server
     */
    public String getName() {
        return name;
    }

    /**
     * Returns the hostname of this server
     * 
     * @return the hostname of this server
     */
    public String getHostname() {
        return hostname;
    }

    /**
     * Returns the binding object for this server
     * 
     * @return the binding object for this server
     */
    public CodeXAPIBindingStub getBinding() {
        return binding;
    }

    /**
     * Returns the session object for this server
     * 
     * @return the session object for this server
     */
    public Session getSession() {
        return session;
    }

    /**
     * Returns the ID of this server
     * 
     * @return the ID of this server
     */
    public int getId() {
        return id;
    }

    // --------- groups part ---------

    /**
     * Returns the projects ({@link CxGroup}) hosted on this server that the
     * user who initialized the connexion is member of
     * 
     * @return the projects ({@link CxGroup} hosted by this server the user is
     *         member of
     * @throws CxException
     */
    public List<CxGroup> getGroups() throws CxException {
        return this.getGroups(false);
    }

    /**
     * Returns the projects ({@link CxGroup}) hosted on this server that the
     * user who initialized the connexion is member of
     * 
     * @param refresh true if you want to enforce to refresh the list from the
     *        CodeX server (some changes can have be done by other users), false
     *        otherwise
     * @return the projects ({@link CxGroup} hosted by this server the user is
     *         member of
     * @throws CxException
     */
    public synchronized List<CxGroup> getGroups(boolean refresh)
                                                                throws CxException {
        if (this.groups == null || refresh) {
            this.initGroups();
        }
        List<CxGroup> groups = new ArrayList<CxGroup>();
        for (CxGroup group : this.groups.values()) {
            groups.add(group);
        }
        Collections.sort(groups, new Comparator<CxGroup>() {

            public int compare(CxGroup o1, CxGroup o2) {
                return o1.getName().compareToIgnoreCase(o2.getName());
            }

        });
        return groups;
    }

    /**
     * Return the project ({@link CxGroup}) hosted on this server with ID
     * groupId
     * 
     * @param groupId the ID of the project ({@link CxGroup})
     * @return the project ({@link CxGroup}) hosted on this server with ID
     *         groupId, or null if no group has been found
     * @throws CxRemoteException
     */
    public CxGroup getGroupById(int groupId) throws CxRemoteException {
        if (this.groups == null || !this.groups.containsKey(groupId)) {
            Group grp;

            try {

                grp = this.binding.getGroupById(this.session.getSession_hash(),
                    groupId);

            } catch (RemoteException e) {
                throw new CxRemoteException(e);
            }

            return new CxGroup(this, grp);
        } else {
            return this.groups.get(groupId);
        }

    }

    /**
     * Init groups with server data.
     * 
     * @throws CxException
     */
    private void initGroups() throws CxException {
        try {
            // Get my projects
            Group[] myGroups = null;

            myGroups = this.binding.getMyProjects(this.session.getSession_hash());

            this.groups = new TreeMap<Integer, CxGroup>();
            for (int i = 0; i < myGroups.length; i++) {
                CxGroup group = new CxGroup(this, myGroups[i]);
                this.groups.put(group.getId(), group);
            }
        } catch (AxisFault axisFault) {
            this.groups = null;
            throw new CxServerException(axisFault);
        } catch (RemoteException re) {
            this.groups = null;
            throw new CxRemoteException(re);
        }
    }

    // --------- events part ---------

    public void addPropertyChangeListener(PropertyChangeListener listener) {
        this.propertyChangeSupport.addPropertyChangeListener(listener);
    }

    public void removePropertyChangeListener(PropertyChangeListener listener) {
        this.propertyChangeSupport.removePropertyChangeListener(listener);
    }

    /**
     * Retrieve the tracker identified by the reference <key, value, group_id>
     * or null if the reference is not found (or is not a by-default tracker
     * reference)
     * 
     * @param key the key of the reference
     * @param value the value of the reference (not used here)
     * @param group_id the group ID the reference is applied to
     * @return the tracker referenced or null if not found
     */
    public CxTracker getTrackerFromArtifactReference(String key, String value,
                                                     int group_id) {
        CxTracker returnedTracker = null;
        try {
            CxGroup group = this.groups.get(group_id);
            if (group != null && key != null) {
                List<CxService> services = group.getServices();
                for (CxService service : services) {
                    if (service instanceof CxServiceTracker) {
                        List<CxTracker> trackers = service.getContentList();
                        for (CxTracker tracker : trackers) {
                            // the references searched are by-default created
                            // references for tracker, means 'trackershortname
                            // #number'
                            if (key.equals(tracker.getItemName())) {
                                return tracker;
                            }
                        }
                    }
                }
            }
        } catch (Exception e) {
            // nothing to do, just return null
        }
        return returnedTracker;
    }

}