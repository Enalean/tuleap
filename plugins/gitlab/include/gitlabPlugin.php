<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Git\Events\GetExternalUsedServiceEvent;
use Tuleap\Gitlab\EventsHandlers\ReferenceAdministrationWarningsCollectorEventHandler;
use Tuleap\Gitlab\Reference\GitlabCommitReference;
use Tuleap\Gitlab\Reference\GitlabCommitFactory;
use Tuleap\Gitlab\Reference\GitlabCommitReferenceBuilder;
use Tuleap\Gitlab\Reference\GitlabCrossReferenceOrganizer;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryWebhookController;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDAO;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferencesParser;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretChecker;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretDao;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookActions;
use Tuleap\Gitlab\Repository\Webhook\WebhookDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\WebhookRepositoryRetriever;
use Tuleap\Gitlab\REST\ResourcesInjector;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Project\Admin\Reference\ReferenceAdministrationWarningsCollectorEvent;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Reference\Nature;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Reference\NatureCollection;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../git/include/gitPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class gitlabPlugin extends Plugin
{
    public const SERVICE_NAME    = "gitlab";
    private const LOG_IDENTIFIER = "gitlab_syslog";

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-gitlab', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Gitlab\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(GetExternalUsedServiceEvent::NAME);

        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);

        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(GetReferenceEvent::NAME);

        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES);
        $this->addHook(Event::GET_REFERENCE_ADMIN_CAPABILITIES);
        $this->addHook(Event::CAN_USER_CREATE_REFERENCE_WITH_THIS_NATURE);
        $this->addHook(NatureCollection::NAME);
        $this->addHook(ReferenceAdministrationWarningsCollectorEvent::NAME);
        $this->addHook(CrossReferenceByNatureOrganizer::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['git'];
    }

    public function getExternalUsedServiceEvent(GetExternalUsedServiceEvent $event): void
    {
        $project = $event->getProject();
        $is_gitlab_used  = $this->isAllowed((int) $project->getGroupId());

        if (! $is_gitlab_used) {
            return;
        }

        $event->addUsedServiceName(self::SERVICE_NAME);
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new ResourcesInjector();
        $injector->declareProjectGitlabResource($params['resources'], $params['project']);
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/plugins/gitlab', function (FastRoute\RouteCollector $r) {
            $r->post('/repository/webhook', $this->getRouteHandler('routePostGitlabRepositoryWebhook'));
        });
    }

    public function routePostGitlabRepositoryWebhook(): GitlabRepositoryWebhookController
    {
        $logger            = BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);
        $reference_manager = ReferenceManager::instance();

        return new GitlabRepositoryWebhookController(
            new WebhookDataExtractor(
                new PostPushCommitWebhookDataExtractor(
                    $logger
                )
            ),
            new WebhookRepositoryRetriever(
                $this->getGitlabRepositoryFactory()
            ),
            new SecretChecker(
                new SecretRetriever(
                    new SecretDao(),
                    new KeyFactory()
                )
            ),
            new WebhookActions(
                new GitlabRepositoryDao(),
                new PostPushWebhookActionProcessor(
                    new CommitTuleapReferencesParser(),
                    new GitlabRepositoryProjectRetriever(
                        new GitlabRepositoryProjectDao(),
                        ProjectManager::instance()
                    ),
                    new CommitTuleapReferenceDAO(),
                    $reference_manager,
                    new TuleapReferenceRetriever(
                        EventManager::instance(),
                        $reference_manager
                    ),
                    $logger,
                ),
                $logger
            ),
            $logger,
            HTTPFactoryBuilder::responseFactory(),
            new SapiEmitter()
        );
    }

    public function getReference(GetReferenceEvent $event): void
    {
        if ($event->getKeyword() === GitlabCommitReference::REFERENCE_NAME) {
            $builder = new GitlabCommitReferenceBuilder(
                new \Tuleap\Gitlab\Reference\ReferenceDao(),
                $this->getGitlabRepositoryFactory()
            );

            $reference = $builder->buildGitlabCommitReference(
                $event->getProject(),
                $event->getKeyword(),
                $event->getValue()
            );

            if ($reference !== null) {
                $event->setReference($reference);
            }
        }
    }

    public function get_plugins_available_keywords_references(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['keywords'][] = GitlabCommitReference::REFERENCE_NAME;
    }

    /** @see \Event::GET_REFERENCE_ADMIN_CAPABILITIES */
    public function get_reference_admin_capabilities(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $reference = $params['reference'];
        \assert($reference instanceof Reference);

        if ($reference->getNature() === 'plugin_gitlab_commit') {
            $params['can_be_deleted'] = false;
            $params['can_be_edited']  = false;
        }
    }

    /** @see \Event::CAN_USER_CREATE_REFERENCE_WITH_THIS_NATURE */
    public function can_user_create_reference_with_this_nature(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['nature'] === 'plugin_gitlab_commit') {
            $params['can_create'] = false;
        }
    }

    private function getGitlabRepositoryFactory(): GitlabRepositoryFactory
    {
        return new GitlabRepositoryFactory(
            new GitlabRepositoryDao()
        );
    }

    public function getAvailableReferenceNatures(NatureCollection $natures): void
    {
        $natures->addNature(
            'plugin_gitlab_commit',
            new Nature(
                GitlabCommitReference::REFERENCE_NAME,
                'fab fa-gitlab',
                dgettext('tuleap-gitlab', 'GitLab commit')
            )
        );
    }

    public function referenceAdministrationWarningsCollectorEvent(ReferenceAdministrationWarningsCollectorEvent $event): void
    {
        (new ReferenceAdministrationWarningsCollectorEventHandler())->handle($event);
    }

    public function crossReferenceByNatureOrganizer(CrossReferenceByNatureOrganizer $organizer): void
    {
        $gitlab_repository_dao = new GitlabRepositoryDao();
        $gitlab_organizer      = new GitlabCrossReferenceOrganizer(
            new GitlabRepositoryFactory($gitlab_repository_dao),
            new GitlabCommitFactory(new CommitTuleapReferenceDAO()),
            ProjectManager::instance(),
            new ProjectAccessChecker(
                PermissionsOverrider_PermissionsOverriderManager::instance(),
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            )
        );
        $gitlab_organizer->organizeGitLabReferences($organizer);
    }
}
