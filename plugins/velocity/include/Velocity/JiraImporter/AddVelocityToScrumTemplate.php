<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Velocity\JiraImporter;

use Tuleap\JiraImport\JiraAgile\ScrumTrackerBuilder;
use Tuleap\Tracker\FormElement\Field\FloatingPointNumber\XML\XMLFloatField;
use Tuleap\Tracker\FormElement\Field\XML\ReadPermission;
use Tuleap\Tracker\FormElement\Field\XML\SubmitPermission;
use Tuleap\Tracker\FormElement\Field\XML\UpdatePermission;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;
use Tuleap\Velocity\Semantic\XML\XMLVelocitySemantic;

final class AddVelocityToScrumTemplate
{
    private const VELOCITY_FIELD_NAME = 'velocity';

    public function addVelocityToStructure(XMLTracker $tracker, IDGenerator $id_generator): XMLTracker
    {
        return $tracker->appendFormElement(
            ScrumTrackerBuilder::DETAILS_RIGHT_COLUMN_NAME,
            (new XMLFloatField($id_generator, self::VELOCITY_FIELD_NAME))
                ->withLabel('Velocity')
                ->withRank(2)
                ->withPermissions(
                    new ReadPermission('UGROUP_ANONYMOUS'),
                    new SubmitPermission('UGROUP_REGISTERED'),
                    new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
                )
        )
            ->withSemantics(
                new XMLVelocitySemantic(new XMLReferenceByName(self::VELOCITY_FIELD_NAME))
            );
    }
}
