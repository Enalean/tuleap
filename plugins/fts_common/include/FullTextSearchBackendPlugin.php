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

namespace Tuleap\FullTextSearchCommon;

use Event;
use EventManager;
use Plugin;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\FullTextSearchCommon\Index\FindIndexSearcher;
use Tuleap\FullTextSearchCommon\Index\InsertItemsIntoIndexMetricCollector;
use Tuleap\FullTextSearchCommon\Index\SearchIndexedItem;
use Tuleap\FullTextSearchCommon\Index\SearchIndexedItemMetricCollector;
use Tuleap\FullTextSearchCommon\REST\ResourcesInjector;
use Tuleap\FullTextSearchCommon\CLI\IdentifyAllItemsToIndexCommand;
use Tuleap\FullTextSearchCommon\CLI\IndexAllPendingItemsCommand;
use Tuleap\FullTextSearchCommon\Index\Asynchronous\IndexingWorkerEventDispatcher;
use Tuleap\FullTextSearchCommon\Index\DeleteIndexedItems;
use Tuleap\FullTextSearchCommon\Index\InsertItemsIntoIndex;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Search\IdentifyAllItemsToIndexEvent;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;
use Tuleap\Search\ItemToIndexBatchQueue;

abstract class FullTextSearchBackendPlugin extends Plugin
{
    #[\Override]
    public function postEnable(): void
    {
        parent::postEnable();
        (EventManager::instance())->dispatch(new IdentifyAllItemsToIndexEvent());
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function findIndexSearcher(FindIndexSearcher $find_index_searcher): void
    {
        $find_index_searcher->searcher = new SearchIndexedItemMetricCollector($this->getIndexSearcher(), Prometheus::instance());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function indexItem(ItemToIndex $item): void
    {
        (new \Tuleap\Queue\EnqueueTask())->enqueue(
            \Tuleap\FullTextSearchCommon\Index\Asynchronous\IndexItemTask::fromItemToIndex($item)
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function removeIndexedItems(IndexedItemsToRemove $items_to_remove): void
    {
        (new \Tuleap\Queue\EnqueueTask())->enqueue(
            \Tuleap\FullTextSearchCommon\Index\Asynchronous\RemoveItemsFromIndexTask::fromItemsToRemove($items_to_remove)
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function workerEvent(WorkerEvent $event): void
    {
        (new IndexingWorkerEventDispatcher($this->getItemInserterWithMetricCollector(), $this->getItemRemover()))->process($event);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectCLICommands(CLICommandsCollector $collector): void
    {
        $collector->addCommand(
            IndexAllPendingItemsCommand::NAME,
            function (): IndexAllPendingItemsCommand {
                return new IndexAllPendingItemsCommand(
                    EventManager::instance(),
                    $this->getBatchQueue(),
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
    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status !== \Project::STATUS_DELETED) {
            return;
        }

        $this->getItemRemover()->deleteIndexedItemsPerProjectID((int) $event->project->getID());
    }

    final protected function getItemInserterWithMetricCollector(): InsertItemsIntoIndex
    {
        return new InsertItemsIntoIndexMetricCollector($this->getItemInserter(), Prometheus::instance());
    }

    abstract protected function getIndexSearcher(): SearchIndexedItem;

    abstract protected function getItemInserter(): InsertItemsIntoIndex;

    abstract protected function getItemRemover(): DeleteIndexedItems;

    abstract protected function getBatchQueue(): ItemToIndexBatchQueue;
}
