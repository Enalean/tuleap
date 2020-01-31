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
        return;
    }

    public function visitDate(Tracker_FormElement_Field_Date $field): void
    {
        return;
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
        return;
    }

    public function visitInteger(Tracker_FormElement_Field_Integer $field): void
    {
        return;
    }

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field): void
    {
        return;
    }

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $field): void
    {
        return;
    }

    public function visitString(Tracker_FormElement_Field_String $field): void
    {
        return;
    }

    public function visitText(Tracker_FormElement_Field_Text $field): void
    {
        return;
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field): void
    {
        return;
    }

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $field): void
    {
        return;
    }

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $field): void
    {
        return;
    }

    public function visitSelectbox(Tracker_FormElement_Field_Selectbox $field): void
    {
        return;
    }

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field): void
    {
        return;
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field): void
    {
        return;
    }

    public function visitArtifactId(Tracker_FormElement_Field_ArtifactId $field): void
    {
        return;
    }

    public function visitPerTrackerArtifactId(Tracker_FormElement_Field_PerTrackerArtifactId $field): void
    {
        return;
    }

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $field): void
    {
        return;
    }

    public function visitBurndown(Tracker_FormElement_Field_Burndown $field): void
    {
        return;
    }

    public function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $field): void
    {
        return;
    }

    public function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $field): void
    {
        return;
    }

    public function visitComputed(Tracker_FormElement_Field_Computed $field): void
    {
        return;
    }

    public function visitExternalField(TrackerFormElementExternalField $element): void
    {
        return;
    }

    public function visitPriority(): void
    {
        return;
    }
}
