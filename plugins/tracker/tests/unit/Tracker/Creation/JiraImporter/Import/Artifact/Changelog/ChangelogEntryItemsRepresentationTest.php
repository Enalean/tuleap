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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

use PHPUnit\Framework\TestCase;

class ChangelogEntryItemsRepresentationTest extends TestCase
{
    public function testItBuildsARepresentationFromAPIResponse(): void
    {
        $response = [
            "fieldId"    => "field01",
            "from"       => null,
            "fromString" => "string01",
            "to"         => null,
            "toString"   => "string02"
        ];

        $representation = ChangelogEntryItemsRepresentation::buildFromAPIResponse($response);

        $this->assertInstanceOf(ChangelogEntryItemsRepresentation::class, $representation);

        $this->assertSame("field01", $representation->getFieldId());
        $this->assertNull($representation->getFrom());
        $this->assertSame("string01", $representation->getFromString());
        $this->assertNull($representation->getTo());
        $this->assertSame("string02", $representation->getToString());

        $response = [
            "fieldId"    => "field02",
            "from"       => "10001",
            "fromString" => "string02",
            "to"         => "10002",
            "toString"   => "string03"
        ];

        $representation = ChangelogEntryItemsRepresentation::buildFromAPIResponse($response);

        $this->assertInstanceOf(ChangelogEntryItemsRepresentation::class, $representation);

        $this->assertSame("field02", $representation->getFieldId());
        $this->assertSame("10001", $representation->getFrom());
        $this->assertSame("string02", $representation->getFromString());
        $this->assertSame("10002", $representation->getTo());
        $this->assertSame("string03", $representation->getToString());
    }

    public function testItReturnsNullIfFieldIdNotProvidedInAPIResponse(): void
    {
        $response = [
            "field"      => "WorklogId",
            "fieldtype"  => "jira",
            "from"       => null,
            "fromString" => null
        ];

        $representation = ChangelogEntryItemsRepresentation::buildFromAPIResponse($response);

        $this->assertNull($representation);
    }

    public function testItThrowsAnExcpetionIfAPIResponseIsNotWellFormed(): void
    {
        $response = [
            "fieldId"    => "field01",
            "fromString" => "string01",
            "to"         => null,
            "toString"   => "string02"
        ];

        $this->expectException(ChangelogAPIResponseNotWellFormedException::class);

        ChangelogEntryItemsRepresentation::buildFromAPIResponse($response);

        $response = [
            "fieldId" => "field01",
            "from"    => "10001",
            "to"         => null,
            "toString"   => "string02"
        ];

        $this->expectException(ChangelogAPIResponseNotWellFormedException::class);

        ChangelogEntryItemsRepresentation::buildFromAPIResponse($response);

        $response = [
            "fieldId"    => "field01",
            "from"       => "10001",
            "fromString" => "string01",
            "to"         => null
        ];

        $this->expectException(ChangelogAPIResponseNotWellFormedException::class);

        ChangelogEntryItemsRepresentation::buildFromAPIResponse($response);

        $response = [
            "fieldId"    => "field01",
            "from"       => "10001",
            "fromString" => "string01",
            "toString"   => "string02"
        ];

        $this->expectException(ChangelogAPIResponseNotWellFormedException::class);

        ChangelogEntryItemsRepresentation::buildFromAPIResponse($response);
    }
}
