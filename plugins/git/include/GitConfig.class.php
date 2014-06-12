<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

/**
 * I returns configuration of the plugin git
 */
class GitConfig {

    const CONFPARAMETER = 'enable_online_edit';

    /**
     * @var gitPlugin
     */
    private $plugin;

    /**
     * @var GitConfig
     */
    private static $_instance;

    public function __construct(gitPlugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * @return GitConfig
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $plugin = PluginManager::instance()->getPluginByName('git');
            self::$_instance = new GitConfig($plugin);
        }
        return self::$_instance;
    }

    public function isOnlineEditEnabled() {
        return $this->plugin->getConfigurationParameter(self::CONFPARAMETER) === '1';
    }

}