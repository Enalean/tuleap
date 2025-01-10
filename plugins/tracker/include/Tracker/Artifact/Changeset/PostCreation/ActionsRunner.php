<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Codendi_HTMLPurifier;
use ConfigNotificationAssignedTo;
use EventManager;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tracker_FormElementFactory;
use Tracker_GlobalNotificationDao;
use Tracker_REST_TrackerRestBuilder;
use TransitionFactory;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Mail\MailLogger;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\EventDatesRetriever;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\EventDescriptionRetriever;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\EventOrganizerRetriever;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\EventSummaryRetriever;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Notifications\ConfigNotificationAssignedToDao;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSender;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderDao;
use Tuleap\Tracker\Notifications\InvolvedNotificationDao;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Notifications\Settings\CalendarEventConfigDao;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChangeDAO;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\PermissionsExporter;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\WorkflowRestBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\User\NotificationOnAllUpdatesRetriever;
use Tuleap\Tracker\Webhook\ArtifactPayloadBuilder;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\Webhook\WebhookStatusLogger;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\Webhook\Emitter as WebhookEmitter;
use UGroupManager;
use UserHelper;
use UserManager;
use UserPreferencesDao;

class ActionsRunner
{
    /**
     * @var PostCreationTask[]
     */
    private array $tasks_that_can_be_run_both_sync_and_async;
    /**
     * @var PostCreationTask[]
     */
    private array $tasks_that_can_be_run_only_async = [];

    public function __construct(
        PostCreationTask ...$post_creation_tasks,
    ) {
        $this->tasks_that_can_be_run_both_sync_and_async = $post_creation_tasks;
    }

    public function addAsyncPostCreationTasks(PostCreationTask ...$post_creation_tasks): void
    {
        $this->tasks_that_can_be_run_only_async = [...$this->tasks_that_can_be_run_only_async, ...$post_creation_tasks];
    }

    public static function build(LoggerInterface $logger): self
    {
        $webhook_dao                   = new WebhookDao();
        $user_manager                  = UserManager::instance();
        $form_element_factory          = Tracker_FormElementFactory::instance();
        $transition_retriever          = new TransitionRetriever(
            new StateFactory(
                TransitionFactory::instance(),
                new SimpleWorkflowDao()
            ),
            new TransitionExtractor()
        );
        $frozen_fields_detector        = new FrozenFieldDetector(
            $transition_retriever,
            new FrozenFieldsRetriever(
                new FrozenFieldsDao(),
                Tracker_FormElementFactory::instance()
            )
        );
        $ugroup_manager                = new UGroupManager();
        $permissions_functions_wrapper = new PermissionsFunctionsWrapper();

        $event_manager  = EventManager::instance();
        $task_collector = $event_manager->dispatch(new PostCreationTaskCollectorEvent($logger));

        $action_runner = new self(
            new ClearArtifactChangesetCacheTask(),
            new EmailNotificationTask(
                new MailLogger(),
                UserHelper::instance(),
                new RecipientsManager(
                    $form_element_factory,
                    $user_manager,
                    new UnsubscribersNotificationDAO(),
                    new UserNotificationSettingsRetriever(
                        new Tracker_GlobalNotificationDao(),
                        new UnsubscribersNotificationDAO(),
                        new UserNotificationOnlyStatusChangeDAO(),
                        new InvolvedNotificationDao()
                    ),
                    new UserNotificationOnlyStatusChangeDAO(),
                    new NotificationOnAllUpdatesRetriever(new UserPreferencesDao())
                ),
                Tracker_Artifact_MailGateway_RecipientFactory::build(),
                new MailGatewayConfig(
                    new MailGatewayConfigDao(),
                ),
                new MailSender(),
                new ConfigNotificationAssignedTo(new ConfigNotificationAssignedToDao()),
                new ConfigNotificationEmailCustomSender(new ConfigNotificationEmailCustomSenderDao()),
                new EmailNotificationAttachmentProvider(
                    new CalendarEventConfigDao(),
                    new EventSummaryRetriever(),
                    new EventDescriptionRetriever(),
                    new EventDatesRetriever(SemanticTimeframeBuilder::build()),
                    new EventOrganizerRetriever(),
                ),
            ),
            new WebhookNotificationTask(
                $logger,
                new WebhookEmitter(
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    HttpClientFactory::createAsyncClient(),
                    new WebhookStatusLogger($webhook_dao)
                ),
                new WebhookFactory($webhook_dao),
                new ArtifactPayloadBuilder(
                    new ChangesetRepresentationBuilder(
                        $user_manager,
                        $form_element_factory,
                        new CommentRepresentationBuilder(
                            CommonMarkInterpreter::build(Codendi_HTMLPurifier::instance())
                        ),
                        new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao()))),
                        new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
                    ),
                    new Tracker_REST_TrackerRestBuilder(
                        $form_element_factory,
                        new FormElementRepresentationsBuilder(
                            $form_element_factory,
                            new PermissionsExporter($frozen_fields_detector),
                            new HiddenFieldsetChecker(
                                new HiddenFieldsetsDetector(
                                    $transition_retriever,
                                    new HiddenFieldsetsRetriever(
                                        new HiddenFieldsetsDao(),
                                        $form_element_factory,
                                    ),
                                    $form_element_factory,
                                ),
                                new FieldsExtractor(),
                            ),
                            new PermissionsForGroupsBuilder(
                                $ugroup_manager,
                                $frozen_fields_detector,
                                $permissions_functions_wrapper,
                            ),
                            new TypePresenterFactory(
                                new TypeDao(),
                                new ArtifactLinksUsageDao(),
                            ),
                        ),
                        new PermissionsRepresentationBuilder(
                            $ugroup_manager,
                            $permissions_functions_wrapper,
                        ),
                        new WorkflowRestBuilder(),
                    ),
                    new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
                ),
            ),
        );

        $action_runner->addAsyncPostCreationTasks(...($task_collector->getAsyncTasks()));

        return $action_runner;
    }

    /**
     * Process actions in synchronous mode.
     * Actions that must be run async will not be processed
     */
    public function processSyncPostCreationActions(Tracker_Artifact_Changeset $changeset, PostCreationTaskConfiguration $configuration): void
    {
        $this->processPostCreationActions($changeset, $configuration, false);
    }

    /**
     * Process actions when executed in background (should not be called by front-end)
     */
    public function processAsyncPostCreationActions(Tracker_Artifact_Changeset $changeset, PostCreationTaskConfiguration $configuration): void
    {
        $this->processPostCreationActions($changeset, $configuration, true);
    }

    private function processPostCreationActions(Tracker_Artifact_Changeset $changeset, PostCreationTaskConfiguration $configuration, bool $execute_async): void
    {
        foreach ($this->tasks_that_can_be_run_both_sync_and_async as $notification_task) {
            $notification_task->execute($changeset, $configuration);
        }

        if ($execute_async) {
            foreach ($this->tasks_that_can_be_run_only_async as $notification_task) {
                $notification_task->execute($changeset, $configuration);
            }
        }
    }
}
