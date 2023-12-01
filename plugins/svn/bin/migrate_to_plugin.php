#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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

use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Repository\CoreRepository;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\SVN\Admin\ImmutableTagCreator;
use Tuleap\SVN\Admin\ImmutableTagDao;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Admin\MailNotificationDao;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Migration\BareRepositoryCreator;
use Tuleap\SVN\Migration\SettingsRetriever;
use Tuleap\SVN\Migration\SvnMigratorException;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Notifications\UgroupsToNotifyDao;
use Tuleap\SVN\Notifications\UsersToNotifyDao;
use Tuleap\SVN\Repository\HookConfigChecker;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\HookConfigSanitizer;
use Tuleap\SVN\Repository\HookConfigUpdator;
use Tuleap\SVN\Repository\HookDao;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RepositoryRegexpBuilder;
use Tuleap\SVN\SvnAdmin;
use Tuleap\SVN\SvnPermissionManager;

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/svnPlugin.php';

function usage()
{
    global $argv;

    echo <<< EOT
Usage: $argv[0] <project> <user>

Migrate the core repository into plugin

  - <project>    The id of the project to import the archive
  - <user>       The user we want to use for creation

EOT;
    exit(1);
}

if (count($argv) != 3) {
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

$system_command = new System_Command();
$logger         = BackendLogger::getDefaultLogger();
$backend_svn    = Backend::instance('SVN');
assert($backend_svn instanceof BackendSVN);
$svn_admin = new SvnAdmin($system_command, $logger, $backend_svn);
$dao       = new Dao();

$hook_dao                    = new HookDao();
$immutable_tag_dao           = new ImmutableTagDao();
$project_history_formatter   = new ProjectHistoryFormatter();
$project_history_dao         = new ProjectHistoryDao();
$access_file_factory         = new AccessFileHistoryFactory(new AccessFileHistoryDao());
$access_file_history_creator = new AccessFileHistoryCreator(
    new AccessFileHistoryDao(),
    $access_file_factory,
    $project_history_dao,
    $project_history_formatter,
    \Tuleap\SVNCore\SvnAccessFileDefaultBlockGenerator::instance(),
);

$repository_creator = new RepositoryCreator(
    new Dao(),
    SystemEventManager::instance(),
    new ProjectHistoryDao(),
    new SvnPermissionManager(
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
    EventManager::instance(),
    Backend::instanceSVN(),
    $access_file_factory
);

$svn_creator = new BareRepositoryCreator(
    $repository_creator,
    new SettingsRetriever(new SVN_Immutable_Tags_DAO(), new SvnNotificationDao(), new SVN_AccessFile_DAO())
);

$permission_manager = new SvnPermissionManager(
    new PermissionsManager(new PermissionsDao())
);

$user = UserManager::instance()->getUserByUserName($argv[2]);
if (! $permission_manager->isAdmin($project, $user)) {
    fwrite(STDERR, "User should be SVN administrator to be able to do the migration\n");
    exit(1);
}

try {
    $repository = CoreRepository::buildToBeCreatedRepository($project);
    $svn_creator->create($repository, $user);
} catch (SvnMigratorException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
