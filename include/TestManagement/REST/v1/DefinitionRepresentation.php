<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use \Tuleap\REST\JsonCast;
use \Tracker_Artifact;
use \Tracker_FormElementFactory;
use \PFUser;

class DefinitionRepresentation extends MinimalDefinitionRepresentation
{
    const FIELD_DESCRIPTION = 'details';

    /**
     * @var String
     */
    public $description;

    public function build(Tracker_Artifact $artifact, Tracker_FormElementFactory $form_element_factory, PFUser $user)
    {
        parent::build($artifact, $form_element_factory, $user);

        $this->description = $this->getFieldValue(self::FIELD_DESCRIPTION)->getText();
    }
}
