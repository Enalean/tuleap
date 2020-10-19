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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class FormattedChangesetValueForListFieldRetriever
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @throws RestException
     */
    public function getFormattedChangesetValueForFieldList(
        string $field_name,
        string $value,
        Artifact $artifact,
        PFUser $user
    ): ?ArtifactValuesRepresentation {
        $field = $this->form_element_factory->getUsedFieldByNameForUser($artifact->getTrackerId(), $field_name, $user);
        if (! $field) {
            return null;
        }

        $binds = [];
        assert($field instanceof \Tracker_FormElement_Field_List);
        if ($field->getBind()) {
            $binds = $field->getBind()->getValuesByKeyword($value);
        }
        $bind = array_pop($binds);
        if (! $bind) {
            throw new RestException(400, 'Invalid status value');
        }

        $value_representation                 = new ArtifactValuesRepresentation();
        $value_representation->field_id       = (int) $field->getId();
        $value_representation->bind_value_ids = [(int) $bind->getId()];

        return $value_representation;
    }
}
