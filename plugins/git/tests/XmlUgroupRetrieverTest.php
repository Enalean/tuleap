<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use TuleapTestCase;
use ProjectUGroup;
use SimpleXMLElement;

class XmlUgroupRetrieverTest extends TuleapTestCase
{

    /**
     * @var XmlUgroupRetriever
     */
    private $retriever;

    public function setUp()
    {
        parent::setUp();

        $this->logger   = mock('Logger');
        $ugroup_manager = mock('UGroupManager');

        $this->retriever = new XmlUgroupRetriever(
            $this->logger,
            $ugroup_manager
        );

        $this->project = aMockProject()->withId(101)->build();

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

        stub($ugroup_manager)->getUGroupByName($this->project, 'Contributors')->returns($this->ugroup_01);
        stub($ugroup_manager)->getUGroupByName($this->project, 'project_members')->returns($this->ugroup_02);
    }

    public function itRetrievesUgroupIdsForAPermission()
    {
        $xml = <<<XML
            <write>
                <ugroup>Contributors</ugroup>
                <ugroup>project_members</ugroup>
            </write>
XML;

        $xml_node = new SimpleXMLElement($xml);
        $expected = array(101, 3);

        $this->assertEqual($this->retriever->getUgroupIdsForPermissionNode($this->project, $xml_node), $expected);
    }

    public function itRetrievesUgroupsForAPermission()
    {
        $xml = <<<XML
            <write>
                <ugroup>Contributors</ugroup>
                <ugroup>project_members</ugroup>
            </write>
XML;

        $xml_node = new SimpleXMLElement($xml);
        $expected = array($this->ugroup_01, $this->ugroup_02);

        $this->assertEqual($this->retriever->getUgroupsForPermissionNode($this->project, $xml_node), $expected);
    }

    public function itThrowsAnExceptionIfUgroupDoesNotExist()
    {
        $xml = <<<XML
            <write>
                <ugroup>Contributors</ugroup>
                <ugroup>project_members</ugroup>
                <ugroup>non_existing</ugroup>
            </write>
XML;

        $xml_node = new SimpleXMLElement($xml);

        expect($this->logger)->error()->once();
        $this->expectException('GitXmlImporterUGroupNotFoundException');

        $this->retriever->getUgroupsForPermissionNode($this->project, $xml_node);
    }

    public function itDoesNotReturnMultipleTimeTheSameUgroup()
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

        $this->assertEqual($this->retriever->getUgroupsForPermissionNode($this->project, $xml_node), $expected);
    }

    public function itDoesNotReturnMultipleTimeTheSameUgroupId()
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

        $this->assertEqual($this->retriever->getUgroupIdsForPermissionNode($this->project, $xml_node), $expected);
    }
}
