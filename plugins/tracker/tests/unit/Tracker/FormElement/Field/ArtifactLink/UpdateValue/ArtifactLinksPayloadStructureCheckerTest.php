<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

use Tracker_FormElement_InvalidFieldValueException;

final class ArtifactLinksPayloadStructureCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsWhenItHasNoParentKeyNorLinksKey()
    {
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);

        (new ArtifactLinksPayloadStructureChecker())->checkPayloadStructure(["the_links_go_here" => []]);
    }

    public function testItThrowsWhenTheLinksKeyDoesNotContainAnArray(): void
    {
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);

        (new ArtifactLinksPayloadStructureChecker())->checkPayloadStructure(["links" => true]);
    }

    public function testItDoesNotThrowWhenThereIsOnlyAParentKey(): void
    {
        $this->expectNotToPerformAssertions();

        (new ArtifactLinksPayloadStructureChecker())->checkPayloadStructure(["parent" => null]);
    }

    public function testItDoesNotThrowWhenThereIsOnlyALinksKeyContainsAnArray(): void
    {
        $this->expectNotToPerformAssertions();

        (new ArtifactLinksPayloadStructureChecker())->checkPayloadStructure(["links" => []]);
    }
}
