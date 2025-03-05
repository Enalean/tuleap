<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\XML;

use Psr\Log\NullLogger;
use Tuleap\GlobalLanguageMock;
use Tuleap\MediawikiStandalone\Permissions\ISearchByProjectStub;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\UGroupRetrieverStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLMediaWikiExporterTest extends TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with('project_ugroup', 'ugroup_project_members_name_key')
            ->willReturn('project_members');
    }

    public function testItExportsMediaWikiPermissions(): void
    {
        $project_members = ProjectUGroupTestBuilder::buildProjectMembers();
        $developers      = ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName('Developers')->build();

        $dao = ISearchByProjectStub::buildWithPermissions(
            [
                $project_members->getId(),
                $developers->getId(),
            ],
            [
                $developers->getId(),
            ],
            [
                $project_members->getId(),
            ],
        );

        $exporter = new XMLMediaWikiExporter(
            new NullLogger(),
            new ProjectPermissionsRetriever($dao),
            UGroupRetrieverStub::buildWithUserGroups($project_members, $developers)
        );

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                 <project />'
        );

        $project = ProjectTestBuilder::aProject()->build();

        $exporter->exportToXml($project, $xml);

        self::assertCount(2, $xml->{'mediawiki-standalone'}->{'read-access'}->ugroup);
        self::assertEquals('project_members', (string) $xml->{'mediawiki-standalone'}->{'read-access'}->ugroup[0]);
        self::assertEquals('Developers', (string) $xml->{'mediawiki-standalone'}->{'read-access'}->ugroup[1]);

        self::assertCount(1, $xml->{'mediawiki-standalone'}->{'write-access'}->ugroup);
        self::assertEquals('Developers', (string) $xml->{'mediawiki-standalone'}->{'write-access'}->ugroup[0]);

        self::assertCount(1, $xml->{'mediawiki-standalone'}->{'admin-access'}->ugroup);
        self::assertEquals('project_members', (string) $xml->{'mediawiki-standalone'}->{'admin-access'}->ugroup[0]);
    }
}
