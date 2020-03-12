<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class AdminVisitorTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;
    public function testVisitAStringShouldFallbackOnField() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_String(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field::class, $visitor->getAdmin());
    }

    public function testVisitAColumnShouldFallbackOnContainer() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Container_Column(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Container::class, $visitor->getAdmin());
    }

    public function testVisitAnOpenListShouldFallbackOnList() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_OpenList(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_List::class, $visitor->getAdmin());
    }

    public function testVisitARichTextShouldFallbackStaticField() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_StaticField_RichText(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_StaticField::class, $visitor->getAdmin());
    }

    public function testVisitSelectbox() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_Selectbox(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_Selectbox::class, $visitor->getAdmin());
    }

    public function testVisitArtifactId() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_ArtifactId(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_ArtifactId::class, $visitor->getAdmin());
    }

    public function testVisitCrossReferences() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_CrossReferences(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_CrossReferences::class, $visitor->getAdmin());
    }

    public function testVisitLastUpdateDate() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_LastUpdateDate(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_LastUpdateDate::class, $visitor->getAdmin());
    }

    public function testVisitMultiSelectbox() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_MultiSelectbox(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_MultiSelectbox::class, $visitor->getAdmin());
    }

    public function testVisitPermissionsOnArtifact() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_PermissionsOnArtifact(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_PermissionsOnArtifact::class, $visitor->getAdmin());
    }

    public function testVisitSubmittedBy() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_SubmittedBy(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_SubmittedBy::class, $visitor->getAdmin());
    }

    public function testVisitSubmittedOn() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_Field_SubmittedOn(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_Field_SubmittedOn::class, $visitor->getAdmin());
    }

    public function testVisitLineBreak() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_StaticField_LineBreak(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_StaticField_LineBreak::class, $visitor->getAdmin());
    }

    public function testVisitSeparator() : void
    {
        $visitor = new Tracker_FormElement_View_Admin_Visitor(array());
        $formElement = new Tracker_FormElement_StaticField_Separator(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        $this->assertInstanceOf(Tracker_FormElement_View_Admin_StaticField_Separator::class, $visitor->getAdmin());
    }
}
