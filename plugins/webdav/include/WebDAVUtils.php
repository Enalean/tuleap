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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyLegacyBool;
use Tuleap\FRS\FRSPermissionManager;

#[ConfigKeyCategory('WebDAV')]
class WebDAVUtils // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    #[ConfigKey('Enable/disable write access for WebDAV plugin (default: false)')]
    #[ConfigKeyLegacyBool(false)]
    public const CONFIG_WRITE_ACCESS_ENABLED = 'webdav_write_access_enabled';

    protected static $instance;

    /**
     * Instance of docman plugin
     *
     * @var DocmanPlugin
     */
    protected $docmanPlugin;

    /**
     * @var Docman_PermissionsManager[]
     */
    private $docman_permission_manager = [];
    /**
     * @var Docman_ItemFactory
     */
    private $docman_item_factory;

    /**
     * We don't permit cloning the singleton (like $webdavutils = clone $utils)
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Returns the instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function setInstance(WebDAVUtils $instance)
    {
        self::$instance = $instance;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

   /**
     * Replaces '/', '%' and '|' by their respective ASCII code
     *
     * @param String $name
     *
     * @return String
     */
    public function convertName($name)
    {
        $name = str_replace('%', '%25', $name);
        $name = str_replace('/', '%2F', $name);
        $name = str_replace('|', '&#124;', $name);
        return $name;
    }

    /**
     * Retrieves the converted HTML special characters
     *
     * @param String $name
     *
     * @return String
     */
    public function unconvertHTMLSpecialChars($name)
    {
        return util_unconvert_htmlspecialchars($this->convertName($name));
    }

    /**
     * Replaces ASCII codes of '/', '%' and '|' by the respective characters
     *
     * @param String $name
     *
     * @return String
     */
    public function retrieveName($name)
    {
        $name = str_replace('%2F', '/', $name);
        $name = str_replace('%25', '%', $name);
        $name = str_replace('&#124;', '|', $name);
        return $name;
    }

    /**
     * For test purpose
     */
    protected function getFRSPermissionManager()
    {
        return FRSPermissionManager::build();
    }

    /**
     * Tests if the user is Superuser, project admin or File release admin
     *
     * @param PFUser $user
     * @param int $project_id
     *
     * @return bool
     */
    public function userIsAdmin($user, $project_id)
    {
        $permission_manager = $this->getFRSPermissionManager();
        $project            = $this->getProjectManager()->getProject($project_id);

        return ($user->isSuperUser() || $permission_manager->isAdmin($project, $user));
    }

    /**
     * Tests if the user is Superuser, or File release admin
     *
     * @param PFUser $user
     * @param int $project_id
     *
     * @return bool
     */
    public function userCanWrite($user, $project_id)
    {
        $permission_manager = $this->getFRSPermissionManager();
        $project            = $this->getProjectManager()->getProject($project_id);
        return $this->isWriteEnabled() && ($user->isSuperUser() || $permission_manager->isAdmin($project, $user));
    }

    /**
     * Returns an instance of ProjectManager
     *
     * @return ProjectManager
     */
    public function getProjectManager()
    {
        $pm = ProjectManager::instance();
        return $pm;
    }

    /**
     * Returns a FRSPackageFactory
     *
     * @return FRSPackageFactory
     */
    public function getPackageFactory()
    {
        return new FRSPackageFactory();
    }

    /**
     * Returns a FRSReleaseFactory
     *
     * @return FRSReleaseFactory
     */
    public function getReleaseFactory()
    {
        return new FRSReleaseFactory();
    }

    /**
     * Returns a FRSFileFactory
     *
     * @return FRSFileFactory
     */
    public function getFileFactory()
    {
        return new FRSFileFactory();
    }

    /**
     * Returns a PermissionsManager instance
     *
     * @return PermissionsManager
     */
    public function getPermissionsManager()
    {
        return PermissionsManager::instance();
    }

    /**
     * Returns event manager instance
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        return EventManager::instance();
    }

    public function getIncomingFileSize($name)
    {
        return filesize(ForgeConfig::get('ftp_incoming_dir') . '/' . $name);
    }

    public function getIncomingFileMd5Sum($file)
    {
        return hash_file('md5', $file);
    }

    public function getDocmanPermissionsManager(Project $project): Docman_PermissionsManager
    {
        return $this->docman_permission_manager[$project->getID()] ?? Docman_PermissionsManager::instance($project->getGroupId());
    }

    public function setDocmanPermissionsManager(\Project $project, Docman_PermissionsManager $permissions_manager): void
    {
        $this->docman_permission_manager[$project->getID()] = $permissions_manager;
    }

    /**
     * Returns a new instance of ItemFactory
     *
     * @return Docman_ItemFactory
     */
    public function getDocmanItemFactory()
    {
        if ($this->docman_item_factory) {
            return $this->docman_item_factory;
        }
        return new Docman_ItemFactory();
    }

    public function setDocmanItemFactory(Docman_ItemFactory $factory): void
    {
        $this->docman_item_factory = $factory;
    }

    /**
     * Returns a new instance of VersionFactory
     *
     * @return Docman_VersionFactory
     */
    public function getVersionFactory()
    {
        return new Docman_VersionFactory();
    }

    public function isWriteEnabled(): bool
    {
        return ForgeConfig::getStringAsBool(self::CONFIG_WRITE_ACCESS_ENABLED);
    }

    /**
     * Use Docman MVC model to perform webdav actions
     *
     */
    public function processDocmanRequest(WebDAV_Request $request, PFUser $current_user): void
    {
        if (! $this->docmanPlugin) {
            $plugin_manager = PluginManager::instance();
            $plugin         = $plugin_manager->getEnabledPluginByName('docman');
            if ($plugin instanceof DocmanPlugin) {
                $this->docmanPlugin = $plugin;
            } else {
                throw new WebDAVExceptionServerError($GLOBALS['Language']->getText('plugin_webdav_common', 'plugin_not_available'));
            }
        }
        $GLOBALS['Response'] = new WebDAV_Response();
        $controller          = new WebDAV_DocmanController($this->docmanPlugin, $request, $current_user);
        $controller->process();

        if ($GLOBALS['Response']->feedbackHasErrors()) {
            throw new WebDAVExceptionServerError($GLOBALS['Response']->getRawFeedback());
        }
    }
}
