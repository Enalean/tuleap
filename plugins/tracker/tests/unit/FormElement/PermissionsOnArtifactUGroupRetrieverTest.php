<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Tracker_FormElement_Field_PermissionsOnArtifact;
use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertArrayNotHasKey;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertSame;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PermissionsOnArtifactUGroupRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItAddsMissingUgroupsKeyIfFieldIsMandatoryAndContentIsNotProvided(): void
    {
        $retriever = new PermissionsOnArtifactUGroupRetriever();

        $value = [];
        $field = new Tracker_FormElement_Field_PermissionsOnArtifact(null, null, null, null, null, null, null, null, true, null, null, null);

        $new_value = $retriever->initializeUGroupsIfNoUGroupsAreChoosenWithRequiredCondition(
            $value,
            $field
        );

        assertArrayHasKey('u_groups', $new_value);
        assertEmpty($new_value['u_groups']);
    }

    public function testItDoesNothingIfFieldIsMandatoryAndContentIsProvided(): void
    {
        $retriever = new PermissionsOnArtifactUGroupRetriever();

        $value = [
            'u_groups' => ['102'],
        ];

        $field = new Tracker_FormElement_Field_PermissionsOnArtifact(null, null, null, null, null, null, null, null, true, null, null, null);

        $new_value = $retriever->initializeUGroupsIfNoUGroupsAreChoosenWithRequiredCondition(
            $value,
            $field
        );

        assertArrayHasKey('u_groups', $new_value);
        assertSame(['102'], $new_value['u_groups']);
    }

    public function testItDoesNothingIfFieldIsNotMandatoryAndContentIsNotProvided(): void
    {
        $retriever = new PermissionsOnArtifactUGroupRetriever();

        $value = [];
        $field = new Tracker_FormElement_Field_PermissionsOnArtifact(null, null, null, null, null, null, null, null, false, null, null, null);

        $new_value = $retriever->initializeUGroupsIfNoUGroupsAreChoosenWithRequiredCondition(
            $value,
            $field
        );

        assertArrayNotHasKey('u_groups', $new_value);
    }

    public function testItDoesNothingIfFieldIsNotMandatoryAndContentIsProvided(): void
    {
        $retriever = new PermissionsOnArtifactUGroupRetriever();

        $value = [
            'u_groups' => ['102'],
        ];

        $field = new Tracker_FormElement_Field_PermissionsOnArtifact(null, null, null, null, null, null, null, null, false, null, null, null);

        $new_value = $retriever->initializeUGroupsIfNoUGroupsAreChoosenWithRequiredCondition(
            $value,
            $field
        );

        assertArrayHasKey('u_groups', $new_value);
        assertSame(['102'], $new_value['u_groups']);
    }
}
