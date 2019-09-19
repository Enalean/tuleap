<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once __DIR__.'/../../bootstrap.php';

class AdminVisitorTest extends TuleapTestCase
{

    public function testVisitAStringShouldFallbackOnField()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_String(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field');
    }

    public function testVisitAColumnShouldFallbackOnContainer()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Container_Column(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Container');
    }

    public function testVisitAnOpenListShouldFallbackOnList()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_OpenList(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_List');
    }

    public function testVisitARichTextShouldFallbackStaticField()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_StaticField_RichText(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_StaticField');
    }

    public function testVisitSelectbox()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_Selectbox(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_Selectbox');
    }

    public function testVisitArtifactId()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_ArtifactId(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_ArtifactId');
    }

    public function testVisitCrossReferences()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_CrossReferences(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_CrossReferences');
    }

    public function testVisitLastUpdateDate()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_LastUpdateDate(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_LastUpdateDate');
    }

    public function testVisitMultiSelectbox()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_MultiSelectbox(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_MultiSelectbox');
    }

    public function testVisitPermissionsOnArtifact()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_PermissionsOnArtifact(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_PermissionsOnArtifact');
    }

    public function testVisitSubmittedBy()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_SubmittedBy(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_SubmittedBy');
    }

    public function testVisitSubmittedOn()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_SubmittedOn(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_Field_SubmittedOn');
    }

    public function testVisitLineBreak()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_StaticField_LineBreak(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_StaticField_LineBreak');
    }

    public function testVisitSeparator()
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_StaticField_Separator(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertIsA($visitor->getAdmin(), 'Tracker_FormElement_View_Admin_StaticField_Separator');
    }
}
