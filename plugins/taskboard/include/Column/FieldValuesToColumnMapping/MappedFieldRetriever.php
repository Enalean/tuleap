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

use Cardwall_FieldProviders_SemanticStatusFieldRetriever;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingDao;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingFactory;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

class MappedFieldRetriever
{
    /**
     * @var Cardwall_FieldProviders_SemanticStatusFieldRetriever
     */
    private $semantic_status_provider;
    /** @var FreestyleMappingFactory */
    private $freestyle_mapping_factory;

    public function __construct(
        Cardwall_FieldProviders_SemanticStatusFieldRetriever $semantic_status_provider,
        FreestyleMappingFactory $freestyle_mapping_factory
    ) {
        $this->semantic_status_provider = $semantic_status_provider;
        $this->freestyle_mapping_factory = $freestyle_mapping_factory;
    }

    public static function build(): self
    {
        return new self(
            new Cardwall_FieldProviders_SemanticStatusFieldRetriever(),
            new FreestyleMappingFactory(new FreestyleMappingDao(), \Tracker_FormElementFactory::instance())
        );
    }

    public function getField(TaskboardTracker $taskboard_tracker): ?Tracker_FormElement_Field_Selectbox
    {
        $mapped_field = $this->freestyle_mapping_factory->getMappedField($taskboard_tracker);
        if ($mapped_field) {
            return $mapped_field;
        }
        return $this->semantic_status_provider->getField($taskboard_tracker->getTracker());
    }
}
