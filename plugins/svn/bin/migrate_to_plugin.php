#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Svn\AccessControl\AccessFileHistoryCreator;
use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\Destructor;
use Tuleap\Svn\Admin\ImmutableTagCreator;
use Tuleap\Svn\Admin\ImmutableTagDao;
use Tuleap\Svn\Admin\ImmutableTagFactory;
use Tuleap\Svn\Admin\MailNotificationDao;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Dao;
use Tuleap\Svn\Migration\BareRepositoryCreator;
use Tuleap\Svn\Migration\RepositoryCopier;
use Tuleap\Svn\Migration\SettingsRetriever;
use Tuleap\Svn\Migration\SvnMigratorException;
use Tuleap\Svn\Notifications\NotificationsEmailsBuilder;
use Tuleap\Svn\Notifications\UgroupsToNotifyDao;
use Tuleap\Svn\Notifications\UsersToNotifyDao;
use Tuleap\Svn\Repository\HookConfigChecker;
use Tuleap\Svn\Repository\HookConfigRetriever;
use Tuleap\Svn\Repository\HookConfigSanitizer;
use Tuleap\Svn\Repository\HookConfigUpdator;
use Tuleap\Svn\Repository\HookDao;
use Tuleap\Svn\Repository\ProjectHistoryFormatter;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\RepositoryCreator;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\RepositoryRegexpBuilder;
use Tuleap\Svn\SvnAdmin;
use Tuleap\Svn\SvnPermissionManager;

require_once 'pre.php';
require_once __DIR__.'/../include/svnPlugin.class.php';

function usage()
{
    global $argv;

    echo <<< EOT
Usage: $argv[0] project repository_name

Migrate the core repository into plugin

  - <project>    The id of the project to import the archive
  - <repository> The repository name we want to create
  - <user>       The user we want to use for creation

EOT;
    exit(1);
}

if (count($argv) != 4) {
    usage();
}

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user   = $posix_user['name'];
if ($sys_user !== 'root' && $sys_user !== 'codendiadm') {
    fwrite(STDERR, 'Insufficient privileges for user ' . $sys_user . PHP_EOL);
    exit(1);
}

$project_manager = ProjectManager::instance();

$project_id = $argv[1];
$project    = $project_manager->getProject($project_id);
if (! $project->getID()) {
    fwrite(STDERR, "ERROR: Project id not found \n");
    exit(1);
}

$repository_name = $argv[2];
$user_name       = $argv[3];

$system_command = new System_Command();
$logger         = new BackendLogger();
$svn_admin      = new SvnAdmin($system_command, $logger, Backend::instance('SVN'));
$dao            = new Dao();

$repository                  = new Repository("", $repository_name, '', '', $project);
$hook_dao                    = new HookDao();
$immutable_tag_dao           = new ImmutableTagDao();
$project_history_formatter   = new ProjectHistoryFormatter();
$project_history_dao         = new ProjectHistoryDao();
$access_file_factory         = new AccessFileHistoryFactory(new AccessFileHistoryDao());
$access_file_history_creator = new AccessFileHistoryCreator(
    new AccessFileHistoryDao(),
    $access_file_factory,
    $project_history_dao,
    $project_history_formatter
);

$repository_creator = new RepositoryCreator(
    new Dao(),
    SystemEventManager::instance(),
    new ProjectHistoryDao(),
    new SvnPermissionManager(
        new \User_ForgeUserGroupFactory(new \UserGroupDao()),
        \PermissionsManager::instance()
    ),
    new HookConfigUpdator(
        $hook_dao,
        $project_history_dao,
        new HookConfigChecker(new HookConfigRetriever($hook_dao, new HookConfigSanitizer())),
        new HookConfigSanitizer(),
        $project_history_formatter
    ),
    $project_history_formatter,
    new ImmutableTagCreator(
        $immutable_tag_dao,
        $project_history_formatter,
        $project_history_dao,
        new ImmutableTagFactory($immutable_tag_dao)
    ),
    $access_file_history_creator,
    new MailNotificationManager(
        new MailNotificationDao(CodendiDataAccess::instance(), new RepositoryRegexpBuilder()),
        new UsersToNotifyDao(),
        new UgroupsToNotifyDao(),
        $project_history_dao,
        new NotificationsEmailsBuilder(),
        new UGroupManager()
    )
);

$repository_manager = new RepositoryManager(
    new Dao(),
    ProjectManager::instance(),
    $svn_admin,
    $logger,
    $system_command,
    new Destructor(new Dao(), $logger),
    $event_manager,
    BackendSVN::instance(),
    $access_file_factory
);

$svn_creator = new BareRepositoryCreator(
    $repository_creator,
    $access_file_history_creator,
    $repository_manager,
    $user_manager,
    Backend::instance(Backend::SVN),
    Backend::instance(Backend::SYSTEM),
    new RepositoryCopier($system_command),
    new SettingsRetriever(new SVN_Immutable_Tags_DAO(), new SvnNotificationDao(), new SVN_AccessFile_DAO())
);

$permission_manager = new SvnPermissionManager(
    new User_ForgeUserGroupFactory(new UserGroupDao()),
    new PermissionsManager(new PermissionsDao())
);

$user = UserManager::instance()->getUserByUserName($user_name);
if (! $permission_manager->isAdmin($project, $user)) {
    fwrite(STDERR, "User should be SVN administrator to be able to do the migration\n");
    exit(1);
}

try {
    $svn_creator->create($repository, $user);
} catch (SvnMigratorException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
