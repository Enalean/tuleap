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
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownField;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\Tracker\FormElement\Field\CrossReferences\CrossReferencesField;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\Files\FilesField;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\FormElement\Field\LastUpdateBy\LastUpdateByField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\LastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\List\CheckboxField;
use Tuleap\Tracker\FormElement\Field\List\MultiSelectboxField;
use Tuleap\Tracker\FormElement\Field\List\OpenListField;
use Tuleap\Tracker\FormElement\Field\List\RadioButtonField;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;
use Tuleap\Tracker\FormElement\Field\PerTrackerArtifactId\PerTrackerArtifactIdField;
use Tuleap\Tracker\FormElement\Field\Priority\PriorityField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\SubmittedByField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\SubmittedOnField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;

/**
 * I visit ChangesetValue objects
 *
 * @see http://en.wikipedia.org/wiki/Visitor_pattern
 */
interface Tracker_FormElement_FieldVisitor // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function visitArtifactLink(ArtifactLinkField $field);

    public function visitDate(DateField $field);

    public function visitFile(FilesField $field);

    public function visitFloat(FloatField $field);

    public function visitInteger(IntegerField $field);

    public function visitOpenList(OpenListField $field);

    public function visitPermissionsOnArtifact(PermissionsOnArtifactField $field);

    public function visitString(StringField $field);

    public function visitText(TextField $field);

    public function visitRadiobutton(RadioButtonField $field);

    public function visitCheckbox(CheckboxField $field);

    public function visitMultiSelectbox(MultiSelectboxField $field);

    public function visitSelectbox(SelectboxField $field);

    public function visitSubmittedBy(SubmittedByField $field);

    public function visitLastModifiedBy(LastUpdateByField $field);

    public function visitArtifactId(ArtifactIdField $field);

    public function visitPerTrackerArtifactId(PerTrackerArtifactIdField $field);

    public function visitCrossReferences(CrossReferencesField $field);

    public function visitBurndown(BurndownField $field);

    public function visitLastUpdateDate(LastUpdateDateField $field);

    public function visitSubmittedOn(SubmittedOnField $field);

    public function visitComputed(ComputedField $field);

    public function visitExternalField(TrackerFormElementExternalField $element);

    public function visitPriority(PriorityField $field);
}
