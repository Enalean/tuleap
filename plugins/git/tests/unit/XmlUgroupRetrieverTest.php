<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use ColinODell\PsrTestLogger\TestLogger;
use Project;
use ProjectUGroup;
use SimpleXMLElement;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XmlUgroupRetrieverTest extends TestCase
{
    private XmlUgroupRetriever $retriever;
    private TestLogger $logger;
    private Project $project;
    private ProjectUGroup $ugroup_01;
    private ProjectUGroup $ugroup_02;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger    = new TestLogger();
        $ugroup_manager  = $this->createMock(UGroupManager::class);
        $this->retriever = new XmlUgroupRetriever($this->logger, $ugroup_manager);

        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->ugroup_01 = new ProjectUGroup([
            'ugroup_id' => 101,
            'name'      => 'Contributors',
            'group_id'  => 101,
        ]);

        $this->ugroup_02 = new ProjectUGroup([
            'ugroup_id' => 3,
            'name'      => 'project_members',
            'group_id'  => 101,
        ]);

        $ugroup_manager->method('getUGroupByName')->with($this->project, self::isString())
            ->willReturnCallback(fn(Project $project, string $name) => match ($name) {
                'Contributors'    => $this->ugroup_01,
                'project_members' => $this->ugroup_02,
                default           => null,
            });
    }

    public function testItRetrievesUgroupIdsForAPermission(): void
    {
        $xml = <<<XML
            <write>
                <ugroup>Contributors</ugroup>
                <ugroup>project_members</ugroup>
            </write>
XML;

        $xml_node = new SimpleXMLElement($xml);
        $expected = [101, 3];

        self::assertEquals($expected, $this->retriever->getUgroupIdsForPermissionNode($this->project, $xml_node));
    }

    public function testItRetrievesUgroupsForAPermission(): void
    {
        $xml = <<<XML
            <write>
                <ugroup>Contributors</ugroup>
                <ugroup>project_members</ugroup>
            </write>
XML;

        $xml_node = new SimpleXMLElement($xml);
        $expected = [$this->ugroup_01, $this->ugroup_02];

        self::assertEquals($expected, $this->retriever->getUgroupsForPermissionNode($this->project, $xml_node));
    }

    public function testItSkipsIfUgroupDoesNotExist(): void
    {
        $xml = <<<XML
            <write>
                <ugroup>Contributors</ugroup>
                <ugroup>project_members</ugroup>
                <ugroup>non_existing</ugroup>
            </write>
XML;

        $xml_node = new SimpleXMLElement($xml);
        $expected = [$this->ugroup_01, $this->ugroup_02];

        self::assertEquals($expected, $this->retriever->getUgroupsForPermissionNode($this->project, $xml_node));
        self::assertTrue($this->logger->hasWarningRecords());
    }

    public function testItDoesNotReturnMultipleTimeTheSameUgroup(): void
    {
        $xml = <<<XML
            <write>
                <ugroup>Contributors</ugroup>
                <ugroup>project_members</ugroup>
                <ugroup>project_members</ugroup>
            </write>
XML;

        $xml_node = new SimpleXMLElement($xml);
        $expected = [$this->ugroup_01, $this->ugroup_02];

        self::assertEquals($expected, $this->retriever->getUgroupsForPermissionNode($this->project, $xml_node));
    }

    public function testItDoesNotReturnMultipleTimeTheSameUgroupId(): void
    {
        $xml = <<<XML
            <write>
                <ugroup>Contributors</ugroup>
                <ugroup>project_members</ugroup>
                <ugroup>project_members</ugroup>
            </write>
XML;

        $xml_node = new SimpleXMLElement($xml);
        $expected = [101, 3];

        self::assertEquals($expected, $this->retriever->getUgroupIdsForPermissionNode($this->project, $xml_node));
    }
}
