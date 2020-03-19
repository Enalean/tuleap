<?php
/**
 * Copyright Enalean (c) 2016 - 2017. All rights reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/svnPlugin.php';

use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Logs\LastAccessDao;
use Tuleap\SVN\Logs\LastAccessUpdater;
use Tuleap\SVN\Notifications\EmailsToBeNotifiedRetriever;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Notifications\UgroupsToNotifyDao;
use Tuleap\SVN\Notifications\UsersToNotifyDao;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RepositoryRegexpBuilder;
use Tuleap\SVN\Admin\MailHeaderManager;
use Tuleap\SVN\Admin\MailHeaderDao;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Admin\MailNotificationDao;
use Tuleap\SVN\Hooks\PostCommit;
use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVN\Commit\CommitInfoEnhancer;
use Tuleap\SVN\SvnAdmin;

try {
    $repository   = $argv[1];
    $revision     = $argv[2];
    $old_revision = $revision - 1;

    $hook = new PostCommit(
        ReferenceManager::instance(),
        new RepositoryManager(
            new Dao(),
            ProjectManager::instance(),
            new SvnAdmin(new System_Command(), SvnPlugin::getLogger(), Backend::instance(Backend::SVN)),
            SvnPlugin::getLogger(),
            new System_Command(),
            new Destructor(
                new Dao(),
                SvnPlugin::getLogger()
            ),
            EventManager::instance(),
            Backend::instance(Backend::SVN),
            new AccessFileHistoryFactory(new AccessFileHistoryDao())
        ),
        new MailHeaderManager(new MailHeaderDao()),
        new EmailsToBeNotifiedRetriever(
            new MailNotificationManager(
                new MailNotificationDao(CodendiDataAccess::instance(), new RepositoryRegexpBuilder()),
                new UsersToNotifyDao(),
                new UgroupsToNotifyDao(),
                new ProjectHistoryDao(),
                new NotificationsEmailsBuilder(),
                new UGroupManager()
            )
        ),
        new MailBuilder(
            TemplateRendererFactory::build(),
            new MailFilter(
                UserManager::instance(),
                new ProjectAccessChecker(
                    PermissionsOverrider_PermissionsOverriderManager::instance(),
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                new MailLogger()
            )
        ),
        new CommitInfoEnhancer(new Svnlook(new System_Command()), new CommitInfo()),
        new LastAccessUpdater(new LastAccessDao()),
        UserManager::instance(),
        EventManager::instance()
    );

    $hook->process($repository, $revision, $old_revision);

    exit(0);
} catch (Exception $exception) {
    fwrite(STDERR, $exception->getMessage());
    exit(1);
}
