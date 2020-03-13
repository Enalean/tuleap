<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\FormElement;

use Tracker_FormElement;
use Tracker_FormElement_Field_File;
use Tuleap\Tracker\REST\v1\TrackerFieldsResource;

class FieldFileRepresentation extends \Tracker_REST_FormElementRepresentation
{
    /**
     * @var string
     */
    public $file_creation_uri;

    /**
     * @var int
     */
    public $max_size_upload;

    public function build(Tracker_FormElement $form_element, $type, array $permissions, ?PermissionsForGroupsRepresentation $permissions_for_groups)
    {
        if (! $form_element instanceof Tracker_FormElement_Field_File) {
            throw new \LogicException('FieldFileRepresentation should only be built from File field');
        }

        parent::build($form_element, $type, $permissions, $permissions_for_groups);
        $this->file_creation_uri = TrackerFieldsResource::ROUTE . '/' . (int) $form_element->getId() . '/files';
        $this->max_size_upload = \ForgeConfig::get('sys_max_size_upload');
    }
}
