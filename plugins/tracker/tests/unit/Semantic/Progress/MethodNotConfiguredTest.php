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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MethodNotConfiguredTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsNullProgressWhenTheSemanticIsNotDefined(): void
    {
        $method = new MethodNotConfigured();
        self::assertEquals('', $method->getErrorMessage());

        $result = $method->computeProgression(ArtifactTestBuilder::anArtifact(101)->build(), UserTestBuilder::buildWithDefaults());
        self::assertEquals('', $result->getErrorMessage());
        self::assertEquals(null, $result->getValue());
    }

    public function testItExportsNothingToREST(): void
    {
        $method = new MethodNotConfigured();
        self::assertNull($method->exportToREST(UserTestBuilder::buildWithDefaults()));
    }

    public function testItExportsNothingToXML(): void
    {
        $xml_data = '<?xml version="1.0" encoding="UTF-8"?><semantics/>';
        $method   = new MethodNotConfigured();
        $root     = new \SimpleXMLElement($xml_data);

        $method->exportToXMl($root, []);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotSaveItsConfiguration(): void
    {
        $method = new MethodNotConfigured();

        $this->assertFalse(
            $method->saveSemanticForTracker(TrackerTestBuilder::aTracker()->build())
        );
    }

    public function testItDoesNotDelete(): void
    {
        $method = new MethodNotConfigured();
        $this->assertFalse(
            $method->deleteSemanticForTracker(TrackerTestBuilder::aTracker()->build())
        );
    }
}
