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

use Tuleap\FullTextSearchMeilisearch\Server\GenerateServerMasterKey;
use Tuleap\FullTextSearchMeilisearch\Server\LocalMeilisearchServer;
use Tuleap\PluginsAdministration\LifecycleHookCommand\PluginExecuteUpdateHookEvent;

require_once __DIR__ . '/../../fts_common/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class fts_meilisearchPlugin extends \Tuleap\FullTextSearchCommon\FullTextSearchBackendPlugin
{
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

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(PluginExecuteUpdateHookEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function executeUpdateHook(PluginExecuteUpdateHookEvent $event): void
    {
        (new GenerateServerMasterKey(new LocalMeilisearchServer(), $event->logger))->generateMasterKey();
    }

    protected function getIndexSearcher(): \Tuleap\FullTextSearchCommon\Index\SearchIndexedItem
    {
        throw new LogicException('Not yet implemented');
    }

    protected function getItemInserter(): \Tuleap\FullTextSearchCommon\Index\InsertItemsIntoIndex
    {
        throw new LogicException('Not yet implemented');
    }

    protected function getItemRemover(): \Tuleap\FullTextSearchCommon\Index\DeleteIndexedItems
    {
        throw new LogicException('Not yet implemented');
    }

    protected function getBatchQueue(): \Tuleap\Search\ItemToIndexBatchQueue
    {
        throw new LogicException('Not yet implemented');
    }
}
