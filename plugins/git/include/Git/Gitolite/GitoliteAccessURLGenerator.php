<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use GitRepository;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyHelp;
use Tuleap\Config\ConfigKeyString;
use Tuleap\ServerHostname;

#[ConfigKeyCategory('Git')]
class GitoliteAccessURLGenerator implements GenerateGitoliteAccessURL
{
    #[ConfigKey('Define a custom SSH URL to get access to the sources')]
    #[ConfigKeyHelp(<<<EOT
    This is mainly useful when SSH doesn't run on the default port (22)

    For convenience, you can either hardcode the URLs or you can use `%server_name%`
    variable that will be replace automatically by the value of `sys_default_domain`

    Exemple if your SSH server runs on port 2222:
    ssh://gitolite@%server_name%:2222/

    You can disable display of this url by activating this variable and setting '' (empty string)
    EOT)]
    public const SSH_URL = 'git_ssh_url';

    #[ConfigKey('Define a custom HTTPS URL to get access to the sources')]
    #[ConfigKeyString('https://%server_name%/plugins/git')]
    #[ConfigKeyHelp(<<<EOT
    For convenience, you can either hardcode the URLs or you can use `%server_name%`
    variable that will be replace automatically by the value of `sys_default_domain`
    EOT)]
    public const HTTP_URL = 'git_http_url';

    public function __construct(private \GitPluginInfo $git_plugin_info)
    {
    }

    public function getSSHURL(GitRepository $repository): string
    {
        $ssh_url = $this->getConfigurationParameter(self::SSH_URL);
        if ($ssh_url === '') {
            return '';
        } elseif (! $ssh_url) {
            $ssh_url = 'ssh://gitolite@' . ServerHostname::rawHostname();
        }
        return $ssh_url . '/' . $repository->getProject()->getUnixName() . '/' . $repository->getFullName() . '.git';
    }

    public function getHTTPURL(GitRepository $repository): string
    {
        $http_url = $this->getConfigurationParameter(self::HTTP_URL);
        if ($http_url) {
            return $http_url . '/' . $repository->getProject()->getUnixName() . '/' . $repository->getFullName() . '.git';
        }
        return '';
    }

    private function getConfigurationParameter($key)
    {
        $value = $this->git_plugin_info->getPropertyValueForName($key);
        if ($value !== false && $value !== null) {
            $value = trim(str_replace('%server_name%', ServerHostname::hostnameWithHTTPSPort(), $value), '/');
        }
        return $value;
    }
}
