<?php
/**
 * Copyright (c) Enalean SAS, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\CustomColumn;

use Tracker_FormElement_Field_Priority;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElement_Field_ArtifactId;
use Tracker_FormElement_Field_PerTrackerArtifactId;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Burndown;
use Tracker_FormElement_Field_Checkbox;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_CrossReferences;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_LastModifiedBy;
use Tracker_FormElement_Field_LastUpdateDate;
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tracker_FormElement_Field_Radiobutton;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_SubmittedBy;
use Tracker_FormElement_Field_SubmittedOn;
use Tracker_FormElement_Field_Text;
use Tracker_FormElement_Field;
use Tracker_Artifact_Changeset;
use Codendi_HTMLPurifier;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;

/**
 * I am responsible of building the replacement string for a given field to be rendered in ValueFormatter
 */
class ReplaceValueVisitor implements Tracker_FormElement_FieldVisitor
{

    /**
     * @var Tracker_Artifact_Changeset
     */
    private $changeset;

    /**
     * @var Tracker_FormElement_Field
     */
    private $field;

    public function __construct(Tracker_FormElement_Field $field, Tracker_Artifact_Changeset $changeset)
    {
        $this->field     = $field;
        $this->changeset = $changeset;
    }

    public function getReplacement()
    {
        return $this->field->accept($this);
    }

    public function visitArtifactLink(Tracker_FormElement_Field_ArtifactLink $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitDate(Tracker_FormElement_Field_Date $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitFile(Tracker_FormElement_Field_File $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitFloat(Tracker_FormElement_Field_Float $field)
    {
        $changeset_value = $this->changeset->getValue($field);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getFloat();
    }

    public function visitInteger(Tracker_FormElement_Field_Integer $field)
    {
        $changeset_value = $this->changeset->getValue($field);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getInteger();
    }

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitString(Tracker_FormElement_Field_String $field)
    {
        $purifier        = Codendi_HTMLPurifier::instance();
        $changeset_value = $this->changeset->getValue($field);
        if (! $changeset_value) {
            return '';
        }

        return $purifier->purify($changeset_value->getText(), CODENDI_PURIFIER_STRIP_HTML);
    }

    public function visitText(Tracker_FormElement_Field_Text $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitArtifactId(Tracker_FormElement_Field_ArtifactId $field)
    {
        return $this->changeset->getArtifact()->getId();
    }

    public function visitPerTrackerArtifactId(Tracker_FormElement_Field_PerTrackerArtifactId $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitBurndown(Tracker_FormElement_Field_Burndown $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitSelectbox(Tracker_FormElement_Field_Selectbox $field)
    {
        $changeset_value = $this->changeset->getValue($field);
        if (! $changeset_value) {
            return '';
        }

        $values = $changeset_value->getListValues();
        if (count($values) === 0) {
            return '';
        }

        reset($values);
        $first_value = current($values);

        return $first_value->getLabel();
    }

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitComputed(Tracker_FormElement_Field_Computed $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitExternalField(TrackerFormElementExternalField $element)
    {
        throw new UnsupportedFieldException();
    }

    public function visitPriority(Tracker_FormElement_Field_Priority $field)
    {
        throw new UnsupportedFieldException();
    }
}
