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

require_once 'pre.php';
require_once __DIR__.'/../include/svnPlugin.class.php';

use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\Destructor;
use Tuleap\Svn\Commit\Svnlook;
use Tuleap\Svn\Dao;
use Tuleap\Svn\Logs\LastAccessDao;
use Tuleap\Svn\Logs\LastAccessUpdater;
use Tuleap\Svn\Notifications\EmailsToBeNotifiedRetriever;
use Tuleap\Svn\Notifications\NotificationsEmailsBuilder;
use Tuleap\Svn\Notifications\UgroupsToNotifyDao;
use Tuleap\Svn\Notifications\UsersToNotifyDao;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\RepositoryRegexpBuilder;
use Tuleap\Svn\Admin\MailHeaderManager;
use Tuleap\Svn\Admin\MailHeaderDao;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Admin\MailNotificationDao;
use Tuleap\Svn\Hooks\PostCommit;
use Tuleap\Svn\Commit\CommitInfo;
use Tuleap\Svn\Commit\CommitInfoEnhancer;
use Tuleap\Svn\SvnAdmin;
use Tuleap\Svn\SvnLogger;

try {

    $repository   = $argv[1];
    $revision     = $argv[2];
    $old_revision = $revision - 1;

    $hook = new PostCommit(
        ReferenceManager::instance(),
        new RepositoryManager(
            new Dao(),
            ProjectManager::instance(),
            new SvnAdmin(new System_Command(), new SvnLogger(), Backend::instance(Backend::SVN)),
            new SvnLogger(),
            new System_Command(),
            new Destructor(
                new Dao(),
                new SvnLogger()
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
            ),
            new UsersToNotifyDao(),
            new UgroupsToNotifyDao(),
            new UGroupManager(),
            UserManager::instance()
        ),
        new MailBuilder(
            TemplateRendererFactory::build(),
            new MailFilter(UserManager::instance(), new URLVerification(), new MailLogger())
        ),
        new CommitInfoEnhancer(new SVNLook(new System_Command()), new CommitInfo()),
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
