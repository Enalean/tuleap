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
use Tuleap\MediawikiStandalone\Permissions\ISaveProjectPermissionsStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\UGroupRetrieverStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLMediaWikiImporterTest extends TestCase
{
    public function testItExportsMediaWikiPermissions(): void
    {
        $project_members = ProjectUGroupTestBuilder::buildProjectMembers();
        $developers      = ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName('Developers')->build();
        $integrators     = ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('Integrators')->build();

        $permissions_saver = ISaveProjectPermissionsStub::buildSelf();

        $importer = new XMLMediaWikiImporter(
            new NullLogger(),
            UGroupRetrieverStub::buildWithUserGroups($project_members, $developers, $integrators),
            $permissions_saver,
        );

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                 <project>
                    <mediawiki-standalone>
                        <read-access>
                            <ugroup><![CDATA[project_members]]></ugroup>
                            <ugroup><![CDATA[Developers]]></ugroup>
                        </read-access>
                        <write-access>
                            <ugroup><![CDATA[project_members]]></ugroup>
                            <ugroup><![CDATA[Integrators]]></ugroup>
                        </write-access>
                        <admin-access>
                            <ugroup><![CDATA[project_members]]></ugroup>
                            <ugroup><![CDATA[Integrators]]></ugroup>
                        </admin-access>
                    </mediawiki-standalone>
                 </project>
                 '
        );

        $project = ProjectTestBuilder::aProject()->build();

        $importer->import($project, $xml);

        self::assertEquals(
            [3, 101],
            $permissions_saver->getCapturedReadersUgroupIds(),
        );
        self::assertEquals(
            [3, 102],
            $permissions_saver->getCapturedWritersUgroupIds(),
        );
        self::assertEquals(
            [3, 102],
            $permissions_saver->getCapturedAdminsUgroupIds(),
        );
    }
}
