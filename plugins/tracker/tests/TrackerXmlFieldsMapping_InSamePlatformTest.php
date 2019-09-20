<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once 'bootstrap.php';

class TrackerXmlFieldsMapping_InSamePlatform_StaticTest extends TuleapTestCase
{

    /** @var array */
    private $xml_mapping;

    /** @var Tracker_FormElement_Field_Selectbox */
    private $list_field;

    /** @var TrackerXmlMappingDataExtractor */
    private $xml_fields_mapping;

    public function setUp()
    {
        parent::setUp();

        $static_value_01 = stub('Tracker_FormElement_Field_List_Bind_StaticValue')->getId()->returns(24076);
        $static_value_02 = stub('Tracker_FormElement_Field_List_Bind_StaticValue')->getId()->returns(24077);
        $static_value_03 = stub('Tracker_FormElement_Field_List_Bind_StaticValue')->getId()->returns(24078);
        $static_value_04 = stub('Tracker_FormElement_Field_List_Bind_StaticValue')->getId()->returns(24079);
        $static_value_05 = stub('Tracker_FormElement_Field_List_Bind_StaticValue')->getId()->returns(24080);
        $static_value_06 = stub('Tracker_FormElement_Field_List_Bind_StaticValue')->getId()->returns(24081);

        $this->list_field = mock('Tracker_FormElement_Field_Selectbox');

        $this->xml_mapping = array(
            "F21840" => $this->list_field,
            "V24058" => $static_value_01,
            "V24059" => $static_value_02,
            "V24060" => $static_value_03,
            "V24061" => $static_value_04,
            "V24062" => $static_value_05,
            "V24063" => $static_value_06,
        );

        $this->xml_fields_mapping = new TrackerXmlFieldsMapping_InSamePlatform($this->xml_mapping);
    }

    public function itGetsNewValueIdForAStaticList()
    {
        $new_value_id = $this->xml_fields_mapping->getNewValueId(24058);

        $this->assertEqual(24058, $new_value_id);
    }
}

class TrackerXmlFieldsMapping_InSamePlatform_UgroupsTest extends TuleapTestCase
{

    /** @var array */
    private $xml_mapping;

    /** @var Tracker_FormElement_Field_Selectbox */
    private $list_field;

    /** @var TrackerXmlMappingDataExtractor */
    private $xml_fields_mapping;

    public function setUp()
    {
        parent::setUp();

        $ugroup_value_01 = stub('Tracker_FormElement_Field_List_Bind_UgroupsValue')->getId()->returns(300);
        $ugroup_value_02 = stub('Tracker_FormElement_Field_List_Bind_UgroupsValue')->getId()->returns(301);
        $ugroup_value_03 = stub('Tracker_FormElement_Field_List_Bind_UgroupsValue')->getId()->returns(302);

        $this->list_field = mock('Tracker_FormElement_Field_Selectbox');

        $this->xml_mapping = array(
            "F21840" => $this->list_field,
            "V198"   => $ugroup_value_01,
            "V200"   => $ugroup_value_02,
            "V201"   => $ugroup_value_03
        );

        $this->xml_fields_mapping = new TrackerXmlFieldsMapping_InSamePlatform($this->xml_mapping);
    }

    public function itGetsNewValueIdForAUgroupList()
    {
        $new_value_id = $this->xml_fields_mapping->getNewValueId(200);

        $this->assertEqual(200, $new_value_id);
    }
}
