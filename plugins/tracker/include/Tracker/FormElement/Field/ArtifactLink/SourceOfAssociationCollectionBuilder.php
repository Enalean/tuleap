<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;

class SourceOfAssociationCollectionBuilder
{
    /**
     * @var SubmittedValueConvertor
     */
    private $submitted_value_convertor;
    /**
     * @var Tracker_FormElementFactory
     */
    private $factory;

    public function __construct(SubmittedValueConvertor $submitted_value_convertor, Tracker_FormElementFactory $factory)
    {
        $this->submitted_value_convertor = $submitted_value_convertor;
        $this->factory                   = $factory;
    }

    public function getSourceOfAssociationCollection(
        Artifact $artifact,
        array $fields_data
    ) {
        $source_of_association_collection = new SourceOfAssociationCollection();

        $old_value      = null;
        $last_changeset = $artifact->getLastChangeset();
        $artlink_fields = $this->factory->getUsedArtifactLinkFields($artifact->getTracker());
        foreach ($artlink_fields as $field) {
            $is_field_part_of_submitted_data = array_key_exists($field->id, $fields_data);
            if (! $is_field_part_of_submitted_data) {
                continue;
            }

            $new_value = $fields_data[$field->id];
            if ($last_changeset) {
                $old_value = $last_changeset->getValue($field);
            }

            $this->submitted_value_convertor->convert(
                $new_value,
                $source_of_association_collection,
                $artifact,
                $old_value
            );
        }

        return $source_of_association_collection;
    }
}
