<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest\Notification;

use GitDao;
use GitRepositoryFactory;
use PermissionsOverrider_PermissionsOverriderManager;
use ProjectManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Dao;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Notification\Strategy\PullRequestNotificationSendMail;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeDAO;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeEvent;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeRetriever;
use Tuleap\PullRequest\Reviewer\Notification\ReviewerChangeNotificationToProcessBuilder;
use Tuleap\Queue\WorkerEvent;

final class PullRequestNotificationSupport
{
    public static function listen(WorkerEvent $event): void
    {
        if ($event->getEventName() !== EventSubjectToNotificationAsynchronousRedisDispatcher::TOPIC) {
            return;
        }

        /** @psalm-var array{event_class:class-string<EventSubjectToNotification>, content:array} $payload */
        $payload = $event->getPayload();

        self::buildSynchronousDispatcher()->dispatch(
            $payload['event_class']::fromWorkerEventPayload($payload['content'])
        );
    }

    public static function buildSynchronousDispatcher(): EventDispatcherInterface
    {
        return new EventSubjectToNotificationSynchronousDispatcher(
            new EventSubjectToNotificationListenerProvider([
                ReviewerChangeEvent::class => [
                    static function (): EventSubjectToNotificationListener {
                        $git_repository_factory = new GitRepositoryFactory(
                            new GitDao(),
                            ProjectManager::instance()
                        );
                        $user_manager           = \UserManager::instance();
                        $html_url_builder       = new HTMLURLBuilder($git_repository_factory, new InstanceBaseURLBuilder());
                        $event_manager          = \EventManager::instance();
                        return new EventSubjectToNotificationListener(
                            new PullRequestNotificationSendMail(
                                new \MailBuilder(
                                    TemplateRendererFactory::build(),
                                    new MailFilter(
                                        $user_manager,
                                        new ProjectAccessChecker(
                                            PermissionsOverrider_PermissionsOverriderManager::instance(),
                                            new RestrictedUserCanAccessProjectVerifier(),
                                            $event_manager
                                        ),
                                        new MailLogger()
                                    )
                                ),
                                new \MailEnhancer(),
                                new PullRequestPermissionChecker(
                                    $git_repository_factory,
                                    new \Tuleap\Project\ProjectAccessChecker(
                                        PermissionsOverrider_PermissionsOverriderManager::instance(),
                                        new RestrictedUserCanAccessProjectVerifier(),
                                        $event_manager
                                    ),
                                    new AccessControlVerifier(
                                        new FineGrainedRetriever(new FineGrainedDao()),
                                        new \System_Command()
                                    )
                                ),
                                $git_repository_factory,
                                $html_url_builder,
                                new LocaleSwitcher()
                            ),
                            new ReviewerChangeNotificationToProcessBuilder(
                                new ReviewerChangeRetriever(
                                    new ReviewerChangeDAO(),
                                    new Factory(
                                        new Dao(),
                                        \ReferenceManager::instance()
                                    ),
                                    $user_manager
                                ),
                                \UserHelper::instance(),
                                $html_url_builder
                            )
                        );
                    }
                ]
            ])
        );
    }
}
