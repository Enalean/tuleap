<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Project\XML;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\Registration\Template\EmptyTemplate;
use Tuleap\Project\UGroups\XML\XMLUserGroup;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\XML\XMLUser;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLUserGroupsTest extends TestCase
{
    use TemporaryTestDirectory;
    use ForgeConfigSandbox;

    #[\Override]
    protected function setUp(): void
    {
        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
    }

    public function testItExportUserGroups(): void
    {
        $user_groups = new XMLUserGroups(
            [
                new XMLUserGroup(
                    \ProjectUGroup::PROJECT_ADMIN_NAME,
                    [new XMLUser('username', 'foo')]
                ),
            ]
        );

        $user_groups_xml = $user_groups->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project></project>'));
        assert($user_groups_xml !== null);

        self::assertEquals(\ProjectUGroup::PROJECT_ADMIN_NAME, $user_groups_xml->xpath('//ugroup[1]')[0]['name']);
        self::assertNotNull($user_groups_xml->xpath('//ugroup[1]')[0]['description']);
    }

    public function testRemplaceUserGroupsFromTemplate(): void
    {
        $xml_file_content_retriever = new XMLFileContentRetriever();
        $empty_template             = new EmptyTemplate(new GlyphFinder(new \EventManager()));
        $xml_file_content_retriever->getSimpleXMLElementFromFilePath($empty_template->getXMLPath())
            ->match(
                function (\SimpleXMLElement $xml_element): void {
                    unset($xml_element->ugroups);

                    $user_group = new XMLUserGroups(
                        [
                            new XMLUserGroup(
                                \ProjectUGroup::PROJECT_ADMIN_NAME,
                                [new XMLUser('username', 'foo')]
                            ),
                        ]
                    );

                    $user_group->export($xml_element);

                    self::assertEquals('foo', (string) $xml_element->xpath('/project/ugroups/ugroup[1]/members/member[1]')[0]);
                },
                static fn(Fault $fault) => self::fail((string) $fault)
            );
    }
}
