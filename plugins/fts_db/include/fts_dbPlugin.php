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

use Tuleap\CLI\CLICommandsCollector;
use Tuleap\FullTextSearchDB\CLI\IdentifyAllItemsToIndexCommand;
use Tuleap\FullTextSearchDB\CLI\IndexAllPendingItemsCommand;
use Tuleap\FullTextSearchDB\Index\Asynchronous\IndexingWorkerEventDispatcher;
use Tuleap\FullTextSearchDB\Index\ItemToIndexQueueImmediate;
use Tuleap\FullTextSearchDB\REST\ResourcesInjector;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Search\IdentifyAllItemsToIndexEvent;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class fts_dbPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-fts_db', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-fts_db', 'Full-Text search DB backend'),
                    dgettext('tuleap-fts_db', 'Full-Text search of items backed by the database')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(ItemToIndex::NAME);
        $this->addHook(IndexedItemsToRemove::NAME);
        $this->addHook(WorkerEvent::NAME);
        $this->addHook(CLICommandsCollector::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getInstallRequirements(): array
    {
        return [new \Tuleap\Plugin\MandatoryAsyncWorkerSetupPluginInstallRequirement(new \Tuleap\Queue\WorkerAvailability())];
    }

    public function postEnable(): void
    {
        parent::postEnable();
        (EventManager::instance())->dispatch(new IdentifyAllItemsToIndexEvent());
    }

    /**
     * @see REST_RESOURCES
     */
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function indexItem(ItemToIndex $item): void
    {
        (new \Tuleap\Queue\EnqueueTask())->enqueue(
            \Tuleap\FullTextSearchDB\Index\Asynchronous\IndexItemTask::fromItemToIndex($item)
        );
    }

    public function removeIndexedItems(IndexedItemsToRemove $items_to_remove): void
    {
        (new \Tuleap\Queue\EnqueueTask())->enqueue(
            \Tuleap\FullTextSearchDB\Index\Asynchronous\RemoveItemsFromIndexTask::fromItemsToRemove($items_to_remove)
        );
    }

    public function workerEvent(WorkerEvent $event): void
    {
        $search_dao = new \Tuleap\FullTextSearchDB\Index\Adapter\SearchDAO();
        (new IndexingWorkerEventDispatcher($search_dao, $search_dao))->process($event);
    }

    public function collectCLICommands(CLICommandsCollector $collector): void
    {
        $collector->addCommand(
            IndexAllPendingItemsCommand::NAME,
            function (): IndexAllPendingItemsCommand {
                return new IndexAllPendingItemsCommand(
                    EventManager::instance(),
                    new ItemToIndexQueueImmediate(new \Tuleap\FullTextSearchDB\Index\Adapter\SearchDAO())
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
}
