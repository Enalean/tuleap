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

namespace Tuleap\Git;

require_once 'bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use SimpleXMLElement;

class XmlUgroupRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XmlUgroupRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger   = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $ugroup_manager = \Mockery::spy(\UGroupManager::class);

        $this->retriever = new XmlUgroupRetriever(
            $this->logger,
            $ugroup_manager
        );

        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);

        $this->ugroup_01 = new ProjectUGroup(array(
            'ugroup_id' => 101,
            'name'      => 'Contributors',
            'group_id'  => 101
        ));

        $this->ugroup_02 = new ProjectUGroup(array(
            'ugroup_id' => 3,
            'name'      => 'project_members',
            'group_id'  => 101
        ));

        $ugroup_manager->shouldReceive('getUGroupByName')->with($this->project, 'Contributors')->andReturns($this->ugroup_01);
        $ugroup_manager->shouldReceive('getUGroupByName')->with($this->project, 'project_members')->andReturns($this->ugroup_02);
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
        $expected = array(101, 3);

        $this->assertEquals($expected, $this->retriever->getUgroupIdsForPermissionNode($this->project, $xml_node));
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
        $expected = array($this->ugroup_01, $this->ugroup_02);

        $this->assertEquals($expected, $this->retriever->getUgroupsForPermissionNode($this->project, $xml_node));
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
        $expected = array($this->ugroup_01, $this->ugroup_02);

        $this->logger->shouldReceive('warning')->once();
        $this->assertEquals($expected, $this->retriever->getUgroupsForPermissionNode($this->project, $xml_node));
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
        $expected = array($this->ugroup_01, $this->ugroup_02);

        $this->assertEquals($expected, $this->retriever->getUgroupsForPermissionNode($this->project, $xml_node));
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
        $expected = array(101, 3);

        $this->assertEquals($expected, $this->retriever->getUgroupIdsForPermissionNode($this->project, $xml_node));
    }
}
