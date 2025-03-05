<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AssignedToRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAssignedToRepresentationCanBeBuiltWhenThereIsNoAssignedToField(): void
    {
        $tracker_form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $tracker_form_element_factory->method('getUsedFieldByNameForUser')->willReturn(null);

        $assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $tracker_form_element_factory,
            RetrieveUserByIdStub::withNoUser(),
            ProvideUserAvatarUrlStub::build(),
        );

        $user      = UserTestBuilder::buildWithDefaults();
        $execution = ArtifactTestBuilder::anArtifact(101)->build();

        $representation = $assigned_to_representation_builder->getAssignedToRepresentationForExecution($user, $execution);

        $this->assertNull($representation);
    }
}
