<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tracker_FormElementFactory;

class AssignedToRepresentationBuilderTest extends TestCase
{
    public function testAssignedToRepresentationCanBeBuiltWhenThereIsNoAssignedToField()
    {
        $tracker_form_element_factory = \Mockery::mock(Tracker_FormElementFactory::class);
        $tracker_form_element_factory->shouldReceive('getUsedFieldByNameForUser')->andReturns(null);
        $user_manager = \Mockery::mock(\UserManager::class);

        $assigned_to_representation_builder = new AssignedToRepresentationBuilder($tracker_form_element_factory, $user_manager);

        $user      = \Mockery::mock(\PFUser::class);
        $execution = \Mockery::mock(\Tracker_Artifact::class);
        $execution->shouldReceive('getTrackerId')->andReturn(1);

        $representation = $assigned_to_representation_builder->getAssignedToRepresentationForExecution($user, $execution);

        $this->assertNull($representation);
    }
}
