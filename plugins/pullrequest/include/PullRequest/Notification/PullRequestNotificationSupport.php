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
use ProjectManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\BranchUpdate\PullRequestUpdateCommitDiff;
use Tuleap\PullRequest\BranchUpdate\PullRequestUpdatedEvent;
use Tuleap\PullRequest\BranchUpdate\PullRequestUpdatedNotificationToProcessBuilder;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Comment\Dao as CommentDao;
use Tuleap\PullRequest\Comment\Notification\PullRequestNewCommentEvent;
use Tuleap\PullRequest\Comment\Notification\PullRequestNewCommentNotificationToProcessBuilder;
use Tuleap\PullRequest\Dao;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\InlineComment\Notification\InlineCommentCodeContextExtractor;
use Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentEvent;
use Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentNotificationToProcessBuilder;
use Tuleap\PullRequest\Notification\Strategy\PullRequestNotificationSendMail;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeDAO;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeEvent;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeRetriever;
use Tuleap\PullRequest\Reviewer\Notification\ReviewerChangeNotificationToProcessBuilder;
use Tuleap\PullRequest\Reviewer\ReviewerDAO;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\PullRequest\StateStatus\PullRequestAbandonedEvent;
use Tuleap\PullRequest\StateStatus\PullRequestAbandonedNotificationToProcessBuilder;
use Tuleap\PullRequest\StateStatus\PullRequestMergedEvent;
use Tuleap\PullRequest\StateStatus\PullRequestMergedNotificationToProcessBuilder;
use Tuleap\PullRequest\Timeline\Dao as TimelineDAO;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerAvailability;
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

    private static function buildSynchronousDispatcher(): EventDispatcherInterface
    {
        return new EventSubjectToNotificationSynchronousDispatcher(
            new EventSubjectToNotificationListenerProvider([
                ReviewerChangeEvent::class => [
                    static function (): EventSubjectToNotificationListener {
                        $git_repository_factory = self::buildGitRepositoryFactory();
                        $html_url_builder       = self::buildHTMLURLBuilder($git_repository_factory);
                        return new EventSubjectToNotificationListener(
                            self::buildPullRequestNotificationSendMail($git_repository_factory, $html_url_builder),
                            new ReviewerChangeNotificationToProcessBuilder(
                                new ReviewerChangeRetriever(
                                    new ReviewerChangeDAO(),
                                    new Factory(
                                        new Dao(),
                                        \ReferenceManager::instance()
                                    ),
                                    \UserManager::instance()
                                ),
                                \UserHelper::instance(),
                                $html_url_builder
                            )
                        );
                    },
                ],
                PullRequestAbandonedEvent::class => [
                    static function (): EventSubjectToNotificationListener {
                        $git_repository_factory = self::buildGitRepositoryFactory();
                        $html_url_builder       = self::buildHTMLURLBuilder($git_repository_factory);
                        $user_manager           = \UserManager::instance();
                        return new EventSubjectToNotificationListener(
                            self::buildPullRequestNotificationSendMail($git_repository_factory, $html_url_builder),
                            new PullRequestAbandonedNotificationToProcessBuilder(
                                $user_manager,
                                new Factory(
                                    new Dao(),
                                    \ReferenceManager::instance()
                                ),
                                new OwnerRetriever(
                                    $user_manager,
                                    new ReviewerRetriever(
                                        $user_manager,
                                        new ReviewerDAO(),
                                        new PullRequestPermissionChecker(
                                            $git_repository_factory,
                                            new \Tuleap\Project\ProjectAccessChecker(
                                                new RestrictedUserCanAccessProjectVerifier(),
                                                \EventManager::instance()
                                            ),
                                            new AccessControlVerifier(
                                                new FineGrainedRetriever(new FineGrainedDao()),
                                                new \System_Command()
                                            )
                                        )
                                    ),
                                    new TimelineDAO()
                                ),
                                new FilterUserFromCollection(),
                                \UserHelper::instance(),
                                $html_url_builder
                            )
                        );
                    },
                ],
                PullRequestMergedEvent::class => [
                    static function (): EventSubjectToNotificationListener {
                        $git_repository_factory = self::buildGitRepositoryFactory();
                        $html_url_builder       = self::buildHTMLURLBuilder($git_repository_factory);
                        $user_manager           = \UserManager::instance();
                        return new EventSubjectToNotificationListener(
                            self::buildPullRequestNotificationSendMail($git_repository_factory, $html_url_builder),
                            new PullRequestMergedNotificationToProcessBuilder(
                                $user_manager,
                                new Factory(
                                    new Dao(),
                                    \ReferenceManager::instance()
                                ),
                                new OwnerRetriever(
                                    $user_manager,
                                    new ReviewerRetriever(
                                        $user_manager,
                                        new ReviewerDAO(),
                                        new PullRequestPermissionChecker(
                                            $git_repository_factory,
                                            new \Tuleap\Project\ProjectAccessChecker(
                                                new RestrictedUserCanAccessProjectVerifier(),
                                                \EventManager::instance()
                                            ),
                                            new AccessControlVerifier(
                                                new FineGrainedRetriever(new FineGrainedDao()),
                                                new \System_Command()
                                            )
                                        )
                                    ),
                                    new TimelineDAO()
                                ),
                                new FilterUserFromCollection(),
                                \UserHelper::instance(),
                                $html_url_builder
                            )
                        );
                    },
                ],
                PullRequestUpdatedEvent::class => [
                    static function (): EventSubjectToNotificationListener {
                        $git_repository_factory = self::buildGitRepositoryFactory();
                        $html_url_builder       = self::buildHTMLURLBuilder($git_repository_factory);
                        $user_manager           = \UserManager::instance();

                        $git_plugin = \PluginFactory::instance()->getPluginByName('git');
                        assert($git_plugin instanceof \GitPlugin);

                        return new EventSubjectToNotificationListener(
                            self::buildPullRequestNotificationSendMail($git_repository_factory, $html_url_builder),
                            new PullRequestUpdatedNotificationToProcessBuilder(
                                $user_manager,
                                new PullRequestRetriever(
                                    new Dao(),
                                ),
                                $git_repository_factory,
                                new OwnerRetriever(
                                    $user_manager,
                                    new ReviewerRetriever(
                                        $user_manager,
                                        new ReviewerDAO(),
                                        new PullRequestPermissionChecker(
                                            $git_repository_factory,
                                            new \Tuleap\Project\ProjectAccessChecker(
                                                new RestrictedUserCanAccessProjectVerifier(),
                                                \EventManager::instance()
                                            ),
                                            new AccessControlVerifier(
                                                new FineGrainedRetriever(new FineGrainedDao()),
                                                new \System_Command()
                                            )
                                        )
                                    ),
                                    new TimelineDAO()
                                ),
                                new FilterUserFromCollection(),
                                \UserHelper::instance(),
                                $html_url_builder,
                                new \Git_GitRepositoryUrlManager(
                                    $git_plugin
                                ),
                                new PullRequestUpdateCommitDiff()
                            )
                        );
                    },
                ],
                PullRequestNewCommentEvent::class => [
                    static function (): EventSubjectToNotificationListener {
                        $git_repository_factory = self::buildGitRepositoryFactory();
                        $html_url_builder       = self::buildHTMLURLBuilder($git_repository_factory);
                        $user_manager           = \UserManager::instance();
                        $reference_manager      = \ReferenceManager::instance();
                        $html_purifier          = \Codendi_HTMLPurifier::instance();
                        return new EventSubjectToNotificationListener(
                            self::buildPullRequestNotificationSendMail($git_repository_factory, $html_url_builder),
                            new PullRequestNewCommentNotificationToProcessBuilder(
                                $user_manager,
                                new Factory(
                                    new Dao(),
                                    $reference_manager
                                ),
                                new CommentRetriever(new CommentDao()),
                                new OwnerRetriever(
                                    $user_manager,
                                    new ReviewerRetriever(
                                        $user_manager,
                                        new ReviewerDAO(),
                                        new PullRequestPermissionChecker(
                                            $git_repository_factory,
                                            new \Tuleap\Project\ProjectAccessChecker(
                                                new RestrictedUserCanAccessProjectVerifier(),
                                                \EventManager::instance()
                                            ),
                                            new AccessControlVerifier(
                                                new FineGrainedRetriever(new FineGrainedDao()),
                                                new \System_Command()
                                            )
                                        )
                                    ),
                                    new TimelineDAO()
                                ),
                                new FilterUserFromCollection(),
                                \UserHelper::instance(),
                                $html_url_builder,
                                new NotificationContentFormatter(
                                    CommonMarkInterpreter::build(
                                        $html_purifier,
                                    ),
                                    $git_repository_factory,
                                    $html_purifier,
                                ),
                            )
                        );
                    },
                ],
                PullRequestNewInlineCommentEvent::class => [
                    static function (): EventSubjectToNotificationListener {
                        $git_repository_factory = self::buildGitRepositoryFactory();
                        $html_url_builder       = self::buildHTMLURLBuilder($git_repository_factory);
                        $user_manager           = \UserManager::instance();
                        $reference_manager      = \ReferenceManager::instance();
                        $html_purifier          = \Codendi_HTMLPurifier::instance();
                        return new EventSubjectToNotificationListener(
                            self::buildPullRequestNotificationSendMail($git_repository_factory, $html_url_builder),
                            new PullRequestNewInlineCommentNotificationToProcessBuilder(
                                $user_manager,
                                new Factory(
                                    new Dao(),
                                    $reference_manager
                                ),
                                new InlineCommentRetriever(new \Tuleap\PullRequest\InlineComment\Dao()),
                                new OwnerRetriever(
                                    $user_manager,
                                    new ReviewerRetriever(
                                        $user_manager,
                                        new ReviewerDAO(),
                                        new PullRequestPermissionChecker(
                                            $git_repository_factory,
                                            new \Tuleap\Project\ProjectAccessChecker(
                                                new RestrictedUserCanAccessProjectVerifier(),
                                                \EventManager::instance()
                                            ),
                                            new AccessControlVerifier(
                                                new FineGrainedRetriever(new FineGrainedDao()),
                                                new \System_Command()
                                            )
                                        )
                                    ),
                                    new TimelineDAO()
                                ),
                                new InlineCommentCodeContextExtractor(
                                    new FileUniDiffBuilder(),
                                    $git_repository_factory
                                ),
                                new FilterUserFromCollection(),
                                \UserHelper::instance(),
                                $html_url_builder,
                                new NotificationContentFormatter(
                                    CommonMarkInterpreter::build(
                                        $html_purifier,
                                    ),
                                    $git_repository_factory,
                                    $html_purifier,
                                ),
                            )
                        );
                    },
                ],
            ])
        );
    }

    private static function buildPullRequestNotificationSendMail(
        GitRepositoryFactory $git_repository_factory,
        HTMLURLBuilder $html_url_builder,
    ): PullRequestNotificationSendMail {
        $event_manager = \EventManager::instance();
        return new PullRequestNotificationSendMail(
            new \MailBuilder(
                TemplateRendererFactory::build(),
                new MailFilter(
                    \UserManager::instance(),
                    new ProjectAccessChecker(
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
        );
    }

    private static function buildHTMLURLBuilder(GitRepositoryFactory $git_repository_factory): HTMLURLBuilder
    {
        return new HTMLURLBuilder($git_repository_factory);
    }

    private static function buildGitRepositoryFactory(): GitRepositoryFactory
    {
        return new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );
    }

    public static function buildDispatcher(\Psr\Log\LoggerInterface $logger): EventDispatcherInterface
    {
        return new EventDispatcherWithFallback(
            $logger,
            new EventSubjectToNotificationAsynchronousRedisDispatcher(
                new QueueFactory($logger),
                new WorkerAvailability()
            ),
            self::buildSynchronousDispatcher()
        );
    }
}
