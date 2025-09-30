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

use Tuleap\FullTextSearchCommon\Index\ItemToIndexPlaintextTransformer;
use Tuleap\Markdown\CommonMarkInterpreter;

require_once __DIR__ . '/../../fts_common/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class fts_dbPlugin extends \Tuleap\FullTextSearchCommon\FullTextSearchBackendPlugin
{
    private const MAX_ITEMS_PER_BATCH = 128;

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-fts_db', __DIR__ . '/../site-content');
    }

    #[\Override]
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

    #[\Override]
    protected function getIndexSearcher(): \Tuleap\FullTextSearchCommon\Index\SearchIndexedItem
    {
        return new \Tuleap\FullTextSearchDB\Index\SearchDAO();
    }

    #[\Override]
    protected function getItemInserter(): \Tuleap\FullTextSearchCommon\Index\InsertItemsIntoIndex
    {
        $html_purifier = Codendi_HTMLPurifier::instance();
        return new ItemToIndexPlaintextTransformer(
            new \Tuleap\FullTextSearchDB\Index\SearchDAO(),
            $html_purifier,
            CommonMarkInterpreter::build($html_purifier)
        );
    }

    #[\Override]
    protected function getItemRemover(): \Tuleap\FullTextSearchCommon\Index\DeleteIndexedItems
    {
        return new \Tuleap\FullTextSearchDB\Index\SearchDAO();
    }

    #[\Override]
    protected function getBatchQueue(): \Tuleap\Search\ItemToIndexBatchQueue
    {
        return new \Tuleap\FullTextSearchCommon\Index\ItemToIndexLimitedBatchQueue($this->getItemInserterWithMetricCollector(), self::MAX_ITEMS_PER_BATCH);
    }
}
