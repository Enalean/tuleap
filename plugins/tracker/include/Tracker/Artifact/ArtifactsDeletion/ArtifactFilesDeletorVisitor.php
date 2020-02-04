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

use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FormElement_Field_ArtifactId;
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
use Tracker_FormElement_Field_PerTrackerArtifactId;
use Tracker_FormElement_Field_Priority;
use Tracker_FormElement_Field_Radiobutton;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_SubmittedBy;
use Tracker_FormElement_Field_SubmittedOn;
use Tracker_FormElement_Field_Text;
use Tracker_FormElement_FieldVisitor;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;

class ArtifactFilesDeletorVisitor implements Tracker_FormElement_FieldVisitor
{
    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    public function __construct(Tracker_Artifact $artifact)
    {
        $this->artifact = $artifact;
    }

    public function visitArtifactLink(Tracker_FormElement_Field_ArtifactLink $field): void
    {
    }

    public function visitDate(Tracker_FormElement_Field_Date $field): void
    {
    }

    public function visitFile(Tracker_FormElement_Field_File $field): void
    {
        $files = [];
        $artifact_changeset_value =  $this->artifact->getValue($field);
        if ($artifact_changeset_value instanceof Tracker_Artifact_ChangesetValue_File) {
            $files = $artifact_changeset_value->getFiles();
        }

        foreach ($files as $file) {
            $file->deleteFiles();
        }
    }

    public function visitFloat(Tracker_FormElement_Field_Float $field): void
    {
    }

    public function visitInteger(Tracker_FormElement_Field_Integer $field): void
    {
    }

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field): void
    {
    }

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $field): void
    {
    }

    public function visitString(Tracker_FormElement_Field_String $field): void
    {
    }

    public function visitText(Tracker_FormElement_Field_Text $field): void
    {
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field): void
    {
    }

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $field): void
    {
    }

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $field): void
    {
    }

    public function visitSelectbox(Tracker_FormElement_Field_Selectbox $field): void
    {
    }

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field): void
    {
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field): void
    {
    }

    public function visitArtifactId(Tracker_FormElement_Field_ArtifactId $field): void
    {
    }

    public function visitPerTrackerArtifactId(Tracker_FormElement_Field_PerTrackerArtifactId $field): void
    {
    }

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $field): void
    {
    }

    public function visitBurndown(Tracker_FormElement_Field_Burndown $field): void
    {
    }

    public function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $field): void
    {
    }

    public function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $field): void
    {
    }

    public function visitComputed(Tracker_FormElement_Field_Computed $field): void
    {
    }

    public function visitExternalField(TrackerFormElementExternalField $element): void
    {
    }

    public function visitPriority(Tracker_FormElement_Field_Priority $field): void
    {
    }
}
