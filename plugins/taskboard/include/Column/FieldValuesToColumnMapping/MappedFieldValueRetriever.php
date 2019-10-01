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

use Tracker_FormElement_Field_List_BindValue;

class MappedFieldValueRetriever
{
    /**
     * @var \Cardwall_OnTop_ConfigFactory
     */
    private $config_factory;
    /**
     * @var MappedFieldRetriever
     */
    private $mapped_field_retriever;

    public function __construct(
        \Cardwall_OnTop_ConfigFactory $config_factory,
        MappedFieldRetriever $mapped_field_retriever
    ) {
        $this->config_factory         = $config_factory;
        $this->mapped_field_retriever = $mapped_field_retriever;
    }

    public function getValueAtLastChangeset(
        \Planning_Milestone $milestone,
        \Tracker_Artifact $artifact,
        \PFUser $user
    ): ?Tracker_FormElement_Field_List_BindValue {
        $config  = $this->config_factory->getOnTopConfig($milestone->getArtifact()->getTracker());
        if (! $config) {
            return null;
        }

        $mapped_field = $this->mapped_field_retriever->getField($config, $artifact->getTracker());
        if (! $mapped_field) {
            return null;
        }

        if (! $mapped_field->userCanRead($user)) {
            return null;
        }

        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        $value = $last_changeset->getValue($mapped_field);
        if (! $value instanceof \Tracker_Artifact_ChangesetValue_List) {
            return null;
        }

        $values = $value->getListValues();
        if (count($values) === 0) {
            return null;
        }

        reset($values);

        return current($values);
    }
}
