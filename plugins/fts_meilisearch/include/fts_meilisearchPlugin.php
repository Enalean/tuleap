<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\GetConfigKeys;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\FullTextSearchCommon\CLI\IdentifyAllItemsToIndexCommand;
use Tuleap\FullTextSearchCommon\FullTextSearchBackendPlugin;
use Tuleap\FullTextSearchCommon\Index\NullIndexHandler;
use Tuleap\FullTextSearchMeilisearch\CLI\PrepareStartMeilisearchServerCommand;
use Tuleap\FullTextSearchMeilisearch\Index\Asynchronous\ProcessPendingItemsToIndexTask;
use Tuleap\FullTextSearchMeilisearch\Index\MeilisearchHandler;
use Tuleap\FullTextSearchMeilisearch\Index\MeilisearchHandlerFactory;
use Tuleap\FullTextSearchMeilisearch\Index\MeilisearchMetadataDAO;
use Tuleap\FullTextSearchMeilisearch\Index\ProgressQueueIndexItemCategoryLogger;
use Tuleap\FullTextSearchMeilisearch\Server\GenerateServerMasterKey;
use Tuleap\FullTextSearchMeilisearch\Server\Administration\MeilisearchAdminSettingsController;
use Tuleap\FullTextSearchMeilisearch\Server\Administration\MeilisearchAdminSettingsPresenter;
use Tuleap\FullTextSearchMeilisearch\Server\Administration\MeilisearchSaveAdminSettingsController;
use Tuleap\FullTextSearchMeilisearch\Server\LocalMeilisearchServer;
use Tuleap\FullTextSearchMeilisearch\Server\RemoteMeilisearchServerSettings;
use Tuleap\Plugin\LifecycleHookCommand\PluginExecuteUpdateHookEvent;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Search\IndexAllPendingItemsEvent;
use Tuleap\Search\ProgressQueueIndexItemCategory;

require_once __DIR__ . '/../../fts_common/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class fts_meilisearchPlugin extends FullTextSearchBackendPlugin implements PluginWithConfigKeys
{
    private const MAX_ITEMS_PER_BATCH = 128;

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-fts_meilisearch', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-fts_meilisearch', 'Full-Text search Meilisearch backend'),
                    dgettext('tuleap-fts_meilisearch', 'Full-Text search of items backed by a Meilisearch instance')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectCLICommands(CLICommandsCollector $collector): void
    {
        parent::collectCLICommands($collector);
        $collector->addCommand(
            PrepareStartMeilisearchServerCommand::NAME,
            function (): PrepareStartMeilisearchServerCommand {
                return new PrepareStartMeilisearchServerCommand(
                    EventManager::instance(),
                    new \Tuleap\Queue\EnqueueTask(),
                );
            }
        );
        $collector->addCommand(
            IdentifyAllItemsToIndexCommand::NAME,
            function (): IdentifyAllItemsToIndexCommand {
                return new IdentifyAllItemsToIndexCommand(EventManager::instance());
            }
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function executeUpdateHook(PluginExecuteUpdateHookEvent $event): void
    {
        (new GenerateServerMasterKey(new LocalMeilisearchServer(), $event->logger))->generateMasterKey();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function workerEvent(WorkerEvent $event): void
    {
        parent::workerEvent($event);
        ProcessPendingItemsToIndexTask::runIndexationProcessIfNeeded(
            $event,
            function () use ($event): void {
                EventManager::instance()->dispatch(
                    new IndexAllPendingItemsEvent(
                        $this->getBatchQueue(),
                        function (string $item_category) use ($event): ProgressQueueIndexItemCategory {
                            return new ProgressQueueIndexItemCategoryLogger($event->getLogger(), $item_category);
                        }
                    )
                );
            }
        );
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(RemoteMeilisearchServerSettings::class);
    }

    protected function getIndexSearcher(): \Tuleap\FullTextSearchCommon\Index\SearchIndexedItem
    {
        return $this->getMeilisearchHandler();
    }

    protected function getItemInserter(): \Tuleap\FullTextSearchCommon\Index\InsertItemsIntoIndex
    {
        $html_purifier = Codendi_HTMLPurifier::instance();
        return new \Tuleap\FullTextSearchCommon\Index\ItemToIndexPlaintextTransformer(
            $this->getMeilisearchHandler(),
            $html_purifier,
            \Tuleap\Markdown\CommonMarkInterpreter::build($html_purifier),
        );
    }

    protected function getItemRemover(): \Tuleap\FullTextSearchCommon\Index\DeleteIndexedItems
    {
        return $this->getMeilisearchHandler();
    }

    protected function getBatchQueue(): \Tuleap\Search\ItemToIndexBatchQueue
    {
        return new \Tuleap\FullTextSearchCommon\Index\ItemToIndexLimitedBatchQueue($this->getItemInserterWithMetricCollector(), self::MAX_ITEMS_PER_BATCH);
    }

    private function getMeilisearchHandler(): MeilisearchHandler|NullIndexHandler
    {
        $factory = new MeilisearchHandlerFactory(
            BackendLogger::getDefaultLogger(),
            new LocalMeilisearchServer(),
            new MeilisearchMetadataDAO(),
            \Tuleap\Http\HTTPFactoryBuilder::requestFactory(),
            \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
            \Tuleap\Http\HttpClientFactory::createClientForInternalTuleapUse(),
            \Tuleap\Http\HttpClientFactory::createClient(),
        );

        return $factory->buildHandler();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $routes): void
    {
        $route_collector = $routes->getRouteCollector();
        $route_collector->get(MeilisearchAdminSettingsController::ADMIN_SETTINGS_URL, $this->getRouteHandler('routeGetAdminSettings'));
        $route_collector->post(MeilisearchAdminSettingsController::ADMIN_SETTINGS_URL, $this->getRouteHandler('routePostAdminSettings'));
    }

    public function routeGetAdminSettings(): MeilisearchAdminSettingsController
    {
        return new MeilisearchAdminSettingsController(
            new LocalMeilisearchServer(),
            new \Tuleap\Admin\AdminPageRenderer(),
            UserManager::instance(),
            new MeilisearchAdminSettingsPresenter(
                ForgeConfig::get(RemoteMeilisearchServerSettings::URL, ''),
                ForgeConfig::exists(RemoteMeilisearchServerSettings::API_KEY),
                ForgeConfig::get(RemoteMeilisearchServerSettings::INDEX_NAME),
                \Tuleap\CSRFSynchronizerTokenPresenter::fromToken(self::buildCSRFTokenAdmin()),
            )
        );
    }

    private static function buildCSRFTokenAdmin(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(MeilisearchAdminSettingsController::ADMIN_SETTINGS_URL);
    }

    public function routePostAdminSettings(): MeilisearchSaveAdminSettingsController
    {
        $config_keys = EventManager::instance()->dispatch(new GetConfigKeys());
        assert($config_keys instanceof GetConfigKeys);

        return new MeilisearchSaveAdminSettingsController(
            new LocalMeilisearchServer(),
            self::buildCSRFTokenAdmin(),
            new \Tuleap\Config\ConfigSet($config_keys, new \Tuleap\Config\ConfigDao()),
            \Tuleap\FullTextSearchMeilisearch\Server\MeilisearchServerURLValidator::buildSelf(),
            \Tuleap\FullTextSearchMeilisearch\Server\MeilisearchAPIKeyValidator::buildSelf(),
            \Tuleap\FullTextSearchMeilisearch\Server\MeilisearchIndexNameValidator::buildSelf(),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(\Tuleap\Http\HTTPFactoryBuilder::responseFactory(), new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())),
            new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter(),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $key_local_meilisearch_server = (new LocalMeilisearchServer())->getCurrentKey();
        $is_using_local_server        = $key_local_meilisearch_server !== null;
        if ($is_using_local_server) {
            return;
        }

        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-fts_meilisearch', 'Meilisearch'),
                MeilisearchAdminSettingsController::ADMIN_SETTINGS_URL
            )
        );
    }
}
