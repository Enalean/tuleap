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

import com.xerox.xrce.codex.jri.exceptions.CxException;
import com.xerox.xrce.codex.jri.exceptions.CxLoginException;
import com.xerox.xrce.codex.jri.exceptions.CxRemoteException;
import com.xerox.xrce.codex.jri.messages.JRIMessages;

/**
 * CxServerConfiguration is the class for server configuration. A server
 * configuration is the association of a CodeX server and an authentification
 * (user/password). This allows you to manage several connexions to several
 * CodeX servers with different users.
 */
public class CxServerConfiguration implements ITooltipable {

    // --------- instance variables part ---------

    /**
     * Default name ("CodeX Server")
     */
    private String name = JRIMessages.getString("CxServerConfiguration.codex_server");

    /**
     * URL of the CodeX Server of this configuration
     */
    private String url = "";

    /**
     * CodeX username of this configuration
     */
    private String username = "";

    /**
     * Password of this configuration
     */
    private String password = "";

    /**
     * Session hash of this configuration
     */
    private String sessionHash = "";

    /**
     * ID of this configuration (internal)
     */
    private int id = 0;

    /**
     * Server of this configuration (can be null if configuration is not
     * currently used).
     */
    private transient CxServer server = null;

    // --------- constructors part ---------

    /**
     * Constructor. Build a configuration with username/password.
     * 
     * @param name name of the configuration
     * @param url hostname of the CodeX server of the configuration
     * @param username username of the configuration
     * @param password password of the configuration
     * @param id ID of the configuration
     */
    /* package */CxServerConfiguration(String name, String url,
            String username, String password, int id) {

        this.setData(name, url, username, password);
        if (this.id == 0)
            this.id = id;

        this.sessionHash = null;
    }

    /**
     * Constructor for getting a configuration without giving the
     * username/password.
     * 
     * @param name name of the configuration
     * @param url hostname of the configuration
     * @param sessionHash session hash of an existing session on this server
     * @param id id of the configuration
     */
    /* package */CxServerConfiguration(String name, String url,
            String sessionHash, int id) {

        this(name, url, "", "", id);

        this.sessionHash = sessionHash;
    }

    // --------- accessing part ---------

    /**
     * Returns the name of this configuration
     * 
     * @return the name of this configuration
     */
    public String getName() {
        return name;
    }

    /**
     * Returns the hostname of the server of this configuration
     * 
     * @return the hostname of the server of this configuration
     */
    public String getUrl() {
        return url;
    }

    /**
     * Returns the username of this configuration
     * 
     * @return the username of this configuration
     */
    public String getUsername() {
        return username;
    }

    /**
     * Returns the password of this configuration
     * 
     * @return the password of this configuration
     */
    public String getPassword() {
        return password;
    }

    /**
     * Returns the id of this configuration
     * 
     * @return the id of this configuration
     */
    public int getId() {
        return id;
    }

    /**
     * Sets the attributes of this configuration
     * 
     * @param name the name of this configuration
     * @param url the hostname of the server of this configuration
     * @param username the username of this configuration
     * @param password the password of this configuration
     */
    /* package */void setData(String name, String url, String username,
                               String password) {
        this.name = name;
        if (url != null) {
            this.url = url.trim();
        } else {
            this.url = url;
        }
        this.username = username;
        this.password = password;
        // logout when configuration changed
        this.logout();
    }

    /**
     * Sets the password of this configuration (used if the password is not
     * stored)
     * 
     * @param password the password of this configuration
     */
    public void setPassword(String password) {
        this.password = password;
    }

    // --------- testing part ---------

    /**
     * Returns true if the configuration is complete, false otherwise. Required
     * attributes for a configuration are
     * <ul>
     * <li>name</li>
     * <li>url</li>
     * <li>username</li>
     * </ul>
     * Password is not required (because user may not want his password to be
     * stored) and will be asked further
     * 
     * @return true if configuration is complete (don't check password).
     */
    public boolean isConfigurationComplete() {
        return this.name != null && this.name.length() != 0 && this.url != null
               && this.url.length() != 0 && this.username != null
               && this.username.length() != 0;
    }

    /**
     * Returns true is the configuration has a password, false otherwise
     * 
     * @return true if the configuration has a password, false otherwise
     */
    public boolean hasPassword() {
        return this.password != null && this.password.length() != 0;
    }

    // --------- connection part ---------

    /**
     * Returns true if the configuration has an active connection to the server,
     * false otherwise
     * 
     * @return true if the configuration has an active connection to the server,
     *         false otherwise
     */
    public boolean isConnected() {
        return (this.server != null && this.server.getSession() != null);
    }

    /**
     * Returns the server of this configuration. This method init the connexion
     * if necessary
     * 
     * @return server (init connection if necessary).
     * @throws CxLoginException
     */
    public CxServer getServer() throws CxException {
        return this.getServer(null);
    }

    /**
     * Returns the server. If it doesn't exist, creates it and intializes it:
     * <ul>
     * <li>from the sessionHash if furnished OR</li>
     * <li>by loggin with user/password</li>
     * </ul>
     * 
     * @param passwordHook used if password is not provided by user (if password
     *        AND hook are null/empty, this method try to open connection with a
     *        blank password).
     * @return server (init connection if necessary).
     * @throws CxLoginException
     */
    public synchronized CxServer getServer(RetrievePasswordHook passwordHook)
                                                                             throws CxException {
        if (this.server == null) {
            try {
                // instance creation
                this.server = new CxServer(this.getUrl(), this.getName(), this.getId());

                if (this.sessionHash != null) {
                    // init connection from existing session

                    this.server.init(this.sessionHash);

                } else {
                    // retrieve password
                    String pass = this.getPassword();
                    if ((pass == null || pass.length() == 0)
                        && passwordHook != null) {
                        pass = passwordHook.getPassword(this);
                    }
                    // init connection
                    this.server.init(pass, this.getUsername());
                }
            } catch (CxLoginException le) {
                this.server = null;
                throw le;
            } catch (CxRemoteException re) {
                this.server = null;
                throw re;
            }
        }
        return this.server;
    }

    /**
     * Close the connexion to the server of this configuration
     */
    public void logout() {
        if (this.server != null) {
            CxServer serverToLogout = this.server;
            this.server = null;
            serverToLogout.logout();
        }
    }

    // --------- inner interface ---------

    /**
     * Hook to retrieve password when it isn't filled by user.
     */
    public static interface RetrievePasswordHook {

        /** @return needed password. */
        String getPassword(CxServerConfiguration configuration);

    }

    /**
     * Returns the tooltip of this configuration
     * 
     * @return the tooltip of this configuration
     */
    public String getToolTip() {
        // TODO Auto-generated method stub
        String tooltip = this.url;
        tooltip += this.isConnected()
                                     ? JRIMessages.getString("CxServerConfiguration.connected")
                                     : JRIMessages.getString("CxServerConfiguration.not_connected");
        return tooltip;
    }

}