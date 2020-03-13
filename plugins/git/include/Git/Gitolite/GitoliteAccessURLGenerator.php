<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 */

namespace Tuleap\Git\Gitolite;

use ForgeConfig;
use GitRepository;

class GitoliteAccessURLGenerator
{
    /**
     * @var \GitPluginInfo
     */
    private $git_plugin_info;

    public function __construct(\GitPluginInfo $git_plugin_info)
    {
        $this->git_plugin_info = $git_plugin_info;
    }

    /**
     * @return string
     */
    public function getSSHURL(GitRepository $repository)
    {
        $ssh_url = $this->getConfigurationParameter('git_ssh_url');
        if ($ssh_url === '') {
            return '';
        } elseif (! $ssh_url) {
            $ssh_url = 'ssh://gitolite@' . ForgeConfig::get('sys_default_domain');
        }
        return  $ssh_url . '/' . $repository->getProject()->getUnixName() . '/' . $repository->getFullName() . '.git';
    }

    /**
     * @return string
     */
    public function getHTTPURL(GitRepository $repository)
    {
        $http_url = $this->getConfigurationParameter('git_http_url');
        if ($http_url) {
            return  $http_url . '/' . $repository->getProject()->getUnixName() . '/' . $repository->getFullName() . '.git';
        }
        return '';
    }

    private function getConfigurationParameter($key)
    {
        $value = $this->git_plugin_info->getPropertyValueForName($key);
        if ($value !== false && $value !== null) {
            $value = str_replace('%server_name%', ForgeConfig::get('sys_default_domain'), $value);
        }
        return $value;
    }
}
