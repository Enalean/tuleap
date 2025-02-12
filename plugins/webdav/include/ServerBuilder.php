<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\WebDAV;

use BrowserPlugin;
use ForgeConfig;
use Sabre\DAV\Locks\Plugin;
use Sabre\DAV\Server;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use WebDAVRoot;
use WebDAVTree;

#[ConfigKeyCategory('WebDAV')]
final class ServerBuilder
{
    #[ConfigKey('Maximum file size allowed in bits (default: 2147583647 bit = 2^31-1 bit =2GB)')]
    #[ConfigKeyInt(2147583647)]
    public const CONFIG_MAX_FILE_SIZE = 'webdav_max_file_size';

    /**
     * @var int
     */
    private $max_file_size;
    /**
     * @var \WebDAVPlugin
     */
    private $plugin;

    public function __construct(\WebDAVPlugin $plugin)
    {
        $this->max_file_size = ForgeConfig::getInt(self::CONFIG_MAX_FILE_SIZE);
        $this->plugin        = $plugin;
    }

    public function getServerOnSubPath(\PFUser $user): \Sabre\DAV\Server
    {
        return $this->getServer($user, WebdavController::ROUTE_BASE);
    }

    private function getServer(\PFUser $user, string $base_uri): \Sabre\DAV\Server
    {
        // Creating the Root directory from WebDAV file system
        $rootDirectory = new WebDAVRoot(
            $this->plugin,
            $user,
            $this->max_file_size,
            \ProjectManager::instance(),
            \WebDAVUtils::getInstance(),
            \PluginManager::instance(),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                \EventManager::instance(),
            )
        );

        // The tree manages all the file objects
        $tree = new WebDAVTree($rootDirectory);

        // Finally, we create the server object. The server object is responsible for making sense out of the WebDAV protocol
        $server = new Server($tree);
        $server->setLogger(\BackendLogger::getDefaultLogger(\WebDAVPlugin::LOG_IDENTIFIER));

        // Base URI is the path used to access to WebDAV server
        $server->setBaseUri($base_uri);

        // The lock manager is responsible for making sure users don't overwrite each others changes.
        // The locks repository is where temporary data related to locks is stored.
        $locks_path = ForgeConfig::get('codendi_cache_dir') . '/plugins/webdav/locks';
        if (! is_dir($locks_path) && ! mkdir($locks_path, 0750, true) && ! is_dir($locks_path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $locks_path));
        }
        $lockBackend = new \Sabre\DAV\Locks\Backend\File($locks_path . '/locks_storage');
        $lockPlugin  = new Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // Creating the browser plugin
        $plugin = new BrowserPlugin();
        $server->addPlugin($plugin);

        // The server is now ready to run
        return $server;
    }
}
