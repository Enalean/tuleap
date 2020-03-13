#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

/* How to deploy this command
 *
 * ln -s /usr/share/tuleap/plugins/gitlfs/bin/git-lfs-authenticate /usr/share/gitolite3/commands/git-lfs-authenticate
 * sed -i -e "/# These are the commands enabled by default/a 'git-lfs-authenticate'," /var/lib/gitolite/.gitolite.rc
 * install -o root -g root -m 0440 /usr/share/tuleap/plugins/gitlfs/etc/sudoers.d/tuleap_gitlfs_authenticate /etc/sudoers.d/tuleap_gitlfs_authenticate
 */

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperationFactory;
use Tuleap\GitLFS\Authorization\User\UserAuthorizationDAO;
use Tuleap\GitLFS\Authorization\User\UserTokenCreator;
use Tuleap\GitLFS\SSHAuthenticate\SSHAuthenticate;
use Tuleap\GitLFS\SSHAuthenticate\SSHAuthenticateResponseBuilder;

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/gitlfsPlugin.php';

try {
    $project_manager = ProjectManager::instance();
    $ssh_auth = new SSHAuthenticate(
        $project_manager,
        UserManager::instance(),
        new GitRepositoryFactory(
            new GitDao(),
            $project_manager
        ),
        new SSHAuthenticateResponseBuilder(
            new UserTokenCreator(
                new SplitTokenVerificationStringHasher(),
                new UserAuthorizationDAO()
            )
        ),
        new UserOperationFactory(),
        PluginManager::instance()->getAvailablePluginByName('gitlfs')
    );
    $response = $ssh_auth->main($_SERVER['GL_USER'], $argv);
    echo \json_encode($response, JSON_FORCE_OBJECT);
} catch (\Tuleap\GitLFS\SSHAuthenticate\InvalidCommandException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
