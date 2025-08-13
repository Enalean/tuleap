<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\ArtifactId\ArtifactIdField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\LastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\FormElement\Field\PerTrackerArtifactId\PerTrackerArtifactIdField;
use Tuleap\Tracker\FormElement\Field\Priority\PriorityField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\SubmittedOnField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;

/**
 * I visit ChangesetValue objects
 *
 * @see http://en.wikipedia.org/wiki/Visitor_pattern
 */
interface Tracker_FormElement_FieldVisitor // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function visitArtifactLink(ArtifactLinkField $field);

    public function visitDate(DateField $field);

    public function visitFile(Tracker_FormElement_Field_File $field);

    public function visitFloat(FloatField $field);

    public function visitInteger(IntegerField $field);

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field);

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $field);

    public function visitString(StringField $field);

    public function visitText(TextField $field);

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field);

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $field);

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $field);

    public function visitSelectbox(SelectboxField $field);

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field);

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field);

    public function visitArtifactId(ArtifactIdField $field);

    public function visitPerTrackerArtifactId(PerTrackerArtifactIdField $field);

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $field);

    public function visitBurndown(Tracker_FormElement_Field_Burndown $field);

    public function visitLastUpdateDate(LastUpdateDateField $field);

    public function visitSubmittedOn(SubmittedOnField $field);

    public function visitComputed(ComputedField $field);

    public function visitExternalField(TrackerFormElementExternalField $element);

    public function visitPriority(PriorityField $field);
}
