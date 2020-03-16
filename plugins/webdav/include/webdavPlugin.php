<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Webdav\Authentication\HeadersSender;

require_once __DIR__ . '/../../docman/include/docmanPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class WebDAVPlugin extends Plugin
{

    /**
     * Constructor of the class
     *
     * @param int $id
     *
     * @return void
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->addHook('url_verification_instance', 'urlVerification', false);
    }

    /**
     * Returns information about the plugin
     *
     *
     * @see src/common/plugin/Plugin#getPluginInfo()
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo instanceof WebDAVPluginInfo) {
            $this->pluginInfo = new WebDAVPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return ['docman'];
    }

    /**
     * Returns the class that will be in charge of the url verification
     *
     * @param Array $params
     *
     */
    public function urlVerification(&$params): void
    {
        if (! $this->urlIsWebDav($params['server_param'])) {
            return;
        }

        $webdavHost                 = $this->getPluginInfo()->getPropertyValueForName('webdav_host');
        $params['url_verification'] = new Webdav_URLVerification($webdavHost);
    }

    private function urlIsWebDav(array $server): bool
    {
        $webdav_host     = $this->getPluginInfo()->getPropertyValueForName('webdav_host');
        $webdav_base_uri = $this->getPluginInfo()->getPropertyValueForName('webdav_base_uri');
        $http_host       = HTTPRequest::instance()->getFromServer('HTTP_HOST');

        return strpos($http_host . $server['REQUEST_URI'], $webdav_host . $webdav_base_uri) !== false;
    }

    /**
     * Setup then return the WebDAV server
     *
     * @return Sabre_DAV_Server
     */
    public function getServer()
    {
        // Authentication
        $auth = new WebDAVAuthentication(new HeadersSender());
        $user = $auth->authenticate();

        // Creating the Root directory from WebDAV file system
        $maxFileSize = $this->getPluginInfo()->getPropertyValueForName('max_file_size');
        $rootDirectory = new WebDAVRoot($this, $user, $maxFileSize, new ProjectDao());

        // The tree manages all the file objects
        $tree = new WebDAVTree($rootDirectory);

        // Finally, we create the server object. The server object is responsible for making sense out of the WebDAV protocol
        $server = new Sabre_DAV_Server($tree);

        // Base URI is the path used to access to WebDAV server
        $server->setBaseUri($this->getPluginInfo()->getPropertyValueForName('webdav_base_uri'));

        // The lock manager is reponsible for making sure users don't overwrite each others changes.
        // The locks repository is where temporary data related to locks is stored.
        $locks_path = $GLOBALS['codendi_cache_dir'] . '/plugins/webdav/locks';
        if (! is_dir($locks_path)) {
            mkdir($locks_path, 0750, true);
        }
        $lockBackend = new Sabre_DAV_Locks_Backend_FS($locks_path);
        $lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // Creating the browser plugin
        $plugin = new BrowserPlugin();
        $server->addPlugin($plugin);

        // The server is now ready to run
        return $server;
    }
}
