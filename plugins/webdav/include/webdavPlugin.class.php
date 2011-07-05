<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once ('common/plugin/Plugin.class.php');
require_once ('ResolvPHP-5.1.6-Compatibility.php');
require_once ('WebDAVAuthentication.class.php');
require_once ('Webdav_URLVerification.class.php');

class WebDAVPlugin extends Plugin {

    /**
     * Constructor of the class
     *
     * @param Integer $id
     *
     * @return void
     */
    function __construct($id) {

        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->_addHook('url_verification_instance', 'urlVerification', false);
        $this->_addHook('WebDAVService', 'addDocmanService', false);

    }

    /**
     * Returns information about the plugin
     *
     * @return String
     *
     * @see src/common/plugin/Plugin#getPluginInfo()
     */
    function getPluginInfo() {

        if (!$this->pluginInfo instanceof WebDAVPluginInfo) {
            include_once('WebDAVPluginInfo.class.php');
            $this->pluginInfo = new WebDAVPluginInfo($this);
        }
        return $this->pluginInfo;

    }

    /**
     * Returns the class that will be in charge of the url verification
     *
     * @param Array $params
     *
     * @return void
     */
    function urlVerification(&$params) {
        $webdavHost = $this->getPluginInfo()->getPropertyValueForName('webdav_host');
        $params['url_verification'] = new Webdav_URLVerification($webdavHost);
    }

    /**
     * Gets the root node of docman service
     *
     * @param Array $params
     *
     * @return void
     */
    function addDocmanService($params) {
        $root = null;
        $em = EventManager::instance();
        $em->processEvent('webdav_root_for_service', array('project' => $params['project'],
                                                           'service' => 'docman',
                                                           'root'    => &$root));
        if ($root) {
            require_once ('FS/WebDAVDocmanFolder.class.php');
            WebDAVDocmanFile::setMaxFileSize($params['maxFileSize']);
            WebDAVDocmanFolder::setMaxFileSize($params['maxFileSize']);
            $docman = new WebDAVDocmanFolder($params['user'] , $params['project'], $root);
            $params['children']['Documents'] = $docman;
        }
    }

    /**
     * Setup then return the WebDAV server
     *
     * @return Sabre_DAV_Server
     */
    function getServer() {

        // Authentication
        $auth = new WebDAVAuthentication();
        $user = $auth->authenticate();

        // Include the SabreDAV library
        $SabreDAVPath = $this->getPluginInfo()->getPropertyValueForName('sabredav_path');
        require_once ($SabreDAVPath.'/lib/Sabre.autoload.php');

        // Creating the Root directory from WebDAV file system
        $maxFileSize = $this->getPluginInfo()->getPropertyValueForName('max_file_size');
        require_once ('FS/WebDAVRoot.class.php');
        $rootDirectory = new WebDAVRoot($this, $user, $maxFileSize);

        // The tree manages all the file objects
        require_once ('WebDAVTree.class.php');
        $tree = new WebDAVTree($rootDirectory);

        // Finally, we create the server object. The server object is responsible for making sense out of the WebDAV protocol
        $server = new Sabre_DAV_Server($tree);

        // Base URI is the path used to access to WebDAV server
        $server->setBaseUri($this->getPluginInfo()->getPropertyValueForName('webdav_base_uri'));

        // The lock manager is reponsible for making sure users don't overwrite each others changes.
        // The locks repository is where temporary data related to locks is stored.
        $lockBackend = new Sabre_DAV_Locks_Backend_FS($GLOBALS['codendi_cache_dir'].'/plugins/webdav/locks');
        $lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // Creating the browser plugin
        require_once ('BrowserPlugin.class.php');
        $plugin = new BrowserPlugin();
        $server->addPlugin($plugin);

        // The server is now ready to run
        return $server;

    }

}

?>
