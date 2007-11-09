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
import java.util.ArrayList;
import java.util.List;

/**
 * CxServerManager is the class to manage server configurations and active
 * connections.
 */
public class CxServerManager {

    // --------- constants part ---------

    /**
     * Constant string for property 'configuration created'
     */
    public static final String PR_CONFIGURATION_CREATED = "configurationCreated";

    /**
     * Constant string for property 'configuration changed'
     */
    public static final String PR_CONFIGURATION_CHANGED = "configurationChanged";

    /**
     * Constant string for property 'configuration removed'
     */
    public static final String PR_CONFIGURATION_REMOVED = "configurationRemoved";

    // ----------attributs part ----------

    /**
     * Auto-increment for server Ids
     */
    private int serverIds = 0;

    // --------- singleton part ---------

    /**
     * Singleton instance of server manager
     */
    private static CxServerManager one = new CxServerManager();

    /**
     * Returns an instance of CxServerManager
     * 
     * @return ServerConfigurationManager instance.
     */
    public static CxServerManager getInstance() {
        return one;
    }

    // --------- instance variables part ---------

    /**
     * Returns the configurations defined in preferences.
     * 
     * @return the list of configuration defined in preferences.
     */
    private final List<CxServerConfiguration> configurations = new ArrayList<CxServerConfiguration>();

    private PropertyChangeSupport propertyChangeSupport = new PropertyChangeSupport(this);

    // --------- constructors part ---------

    /**
     * Constructor
     */
    private CxServerManager() {
        // avoid instance creation
    }

    // --------- configurations part ---------

    /**
     * Returns the configuration
     * 
     * @return the list of all server configurations.
     */
    public List<CxServerConfiguration> getConfigurations() {
        return this.configurations;
    }

    /**
     * Retrieve configuration with a given name
     * 
     * @param name a server configuration name.
     * @return server configuration named 'name', or null if not found.
     */
    public CxServerConfiguration getConfiguration(String name) {
        for (CxServerConfiguration configuration : this.configurations) {
            if (configuration.getName().equals(name)) {
                return configuration;
            }
        }
        return null;
    }

    /**
     * Create a new server configuration in manager.
     * 
     * @param name name of new configuration
     * @param url url of server
     * @param username username
     * @param password password
     * @return the new configuration
     */
    public CxServerConfiguration createConfiguration(String name, String url,
                                                     String username,
                                                     String password) {
        this.serverIds++;
        CxServerConfiguration configuration = new CxServerConfiguration(name, url, username, password, serverIds);
        this.configurations.add(configuration);
        this.propertyChangeSupport.firePropertyChange(PR_CONFIGURATION_CREATED,
            null, configuration);
        return configuration;
    }

    /**
     * Create a new server configuration in manager from and existing session.
     * 
     * @param name name of new configuration
     * @param url url of server
     * @param sessionHash Session Hash code
     * @return the new configuration
     */
    public CxServerConfiguration createConfiguration(String name, String url,
                                                     String sessionHash) {
        this.serverIds++;
        CxServerConfiguration configuration = new CxServerConfiguration(name, url, sessionHash, serverIds);
        this.configurations.add(configuration);
        this.propertyChangeSupport.firePropertyChange(PR_CONFIGURATION_CREATED,
            null, configuration);
        return configuration;
    }

    /**
     * Change configuration.
     * 
     * @param configuration configuration to be changed
     * @param name new name of new configuration
     * @param url new url of server
     * @param username new username
     * @param password new password
     */
    public void editConfiguration(CxServerConfiguration configuration,
                                  String name, String url, String username,
                                  String password) {
        configuration.setData(name, url, username, password);
        this.propertyChangeSupport.firePropertyChange(PR_CONFIGURATION_CHANGED,
            null, configuration);
    }

    /**
     * Remove a server configuration from manager.
     * 
     * @param configuration the server configuration to remove.
     */
    public void removeConfiguration(CxServerConfiguration configuration) {
        configuration.logout();
        this.configurations.remove(configuration);
        this.propertyChangeSupport.firePropertyChange(PR_CONFIGURATION_REMOVED,
            configuration, null);
    }

    /**
     * Remove all configurations (=> logout all connections).
     */
    public void clean() {
        this.configurations.clear();
        // TODO: remove active servers
    }

    // --------- events part ---------

    public void addPropertyChangeListener(PropertyChangeListener listener) {
        this.propertyChangeSupport.addPropertyChangeListener(listener);
    }

    public void removePropertyChangeListener(PropertyChangeListener listener) {
        this.propertyChangeSupport.removePropertyChangeListener(listener);
    }

}
