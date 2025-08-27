<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Tracker_Artifact_ChangesetValue_File;
use Tracker_FormElement_FieldVisitor;
use Tuleap\Tracker\Artifact\Artifact;
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

class ArtifactFilesDeletorVisitor implements Tracker_FormElement_FieldVisitor
{
    /**
     * @var Artifact
     */
    private $artifact;

    public function __construct(Artifact $artifact)
    {
        $this->artifact = $artifact;
    }

    public function visitArtifactLink(ArtifactLinkField $field): void
    {
    }

    public function visitDate(DateField $field): void
    {
    }

    public function visitFile(FilesField $field): void
    {
        $files                    = [];
        $artifact_changeset_value =  $this->artifact->getValue($field);
        if ($artifact_changeset_value instanceof Tracker_Artifact_ChangesetValue_File) {
            $files = $artifact_changeset_value->getFiles();
        }

        foreach ($files as $file) {
            $file->deleteFiles();
        }
    }

    public function visitFloat(FloatField $field): void
    {
    }

    public function visitInteger(IntegerField $field): void
    {
    }

    public function visitOpenList(OpenListField $field): void
    {
    }

    public function visitPermissionsOnArtifact(PermissionsOnArtifactField $field): void
    {
    }

    public function visitString(StringField $field): void
    {
    }

    public function visitText(TextField $field): void
    {
    }

    public function visitRadiobutton(RadioButtonField $field): void
    {
    }

    public function visitCheckbox(CheckboxField $field): void
    {
    }

    public function visitMultiSelectbox(MultiSelectboxField $field): void
    {
    }

    public function visitSelectbox(SelectboxField $field): void
    {
    }

    public function visitSubmittedBy(SubmittedByField $field): void
    {
    }

    public function visitLastModifiedBy(LastUpdateByField $field): void
    {
    }

    public function visitArtifactId(ArtifactIdField $field): void
    {
    }

    public function visitPerTrackerArtifactId(PerTrackerArtifactIdField $field): void
    {
    }

    public function visitCrossReferences(CrossReferencesField $field): void
    {
    }

    public function visitBurndown(BurndownField $field): void
    {
    }

    public function visitLastUpdateDate(LastUpdateDateField $field): void
    {
    }

    public function visitSubmittedOn(SubmittedOnField $field): void
    {
    }

    public function visitComputed(ComputedField $field): void
    {
    }

    public function visitExternalField(TrackerFormElementExternalField $element): void
    {
    }

    public function visitPriority(PriorityField $field): void
    {
    }
}
