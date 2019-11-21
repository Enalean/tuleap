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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Cardwall_Column;
use Cardwall_FieldProviders_SemanticStatusFieldRetriever;
use Tracker;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingDao;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingFactory;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

class MappedValuesRetriever
{
    /** @var FreestyleMappingFactory */
    private $freestyle_mapping_factory;
    /** @var Cardwall_FieldProviders_SemanticStatusFieldRetriever */
    private $status_retriever;

    public function __construct(
        FreestyleMappingFactory $freestyle_mapping_factory,
        Cardwall_FieldProviders_SemanticStatusFieldRetriever $status_retriever
    ) {
        $this->freestyle_mapping_factory = $freestyle_mapping_factory;
        $this->status_retriever          = $status_retriever;
    }

    public static function build(): self
    {
        return new self(
            new FreestyleMappingFactory(new FreestyleMappingDao(), \Tracker_FormElementFactory::instance()),
            new Cardwall_FieldProviders_SemanticStatusFieldRetriever()
        );
    }

    public function getValuesMappedToColumn(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column
    ): MappedValuesInterface {
        if ($this->freestyle_mapping_factory->doesFreestyleMappingExist($taskboard_tracker)) {
            return $this->freestyle_mapping_factory->getValuesMappedToColumn($taskboard_tracker, $column);
        }
        return $this->matchStatusValuesByDuckTyping($taskboard_tracker->getTracker(), $column);
    }

    private function matchStatusValuesByDuckTyping(Tracker $tracker, Cardwall_Column $column): MappedValuesInterface
    {
        $status_field = $this->status_retriever->getField($tracker);
        if (! $status_field) {
            return new EmptyMappedValues();
        }
        foreach ($status_field->getVisibleValuesPlusNoneIfAny() as $value) {
            if ($column->getLabel() === $value->getLabel()) {
                return new MappedValues([(int) $value->getId()]);
            }
        }
        return new EmptyMappedValues();
    }
}
