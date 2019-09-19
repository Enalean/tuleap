<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\ChangesetValueComputed;

/**
 * I visit ChangesetValue objects
 *
 * @see http://en.wikipedia.org/wiki/Visitor_pattern
 */
interface Tracker_Artifact_ChangesetValueVisitor
{

    public function visitArtifactLink(Tracker_Artifact_ChangesetValue_ArtifactLink $changeset_value);
    public function visitDate(Tracker_Artifact_ChangesetValue_Date $changeset_value);
    public function visitFile(Tracker_Artifact_ChangesetValue_File $changeset_value);
    public function visitFloat(Tracker_Artifact_ChangesetValue_Float $changeset_value);
    public function visitInteger(Tracker_Artifact_ChangesetValue_Integer $changeset_value);
    public function visitList(Tracker_Artifact_ChangesetValue_List $changeset_value);
    public function visitOpenList(Tracker_Artifact_ChangesetValue_OpenList $changeset_value);
    public function visitPermissionsOnArtifact(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact $changeset_value);
    public function visitString(Tracker_Artifact_ChangesetValue_String $changeset_value);
    public function visitText(Tracker_Artifact_ChangesetValue_Text $changeset_value);
    public function visitComputed(ChangesetValueComputed $changeset_value);
    public function visitExternalField(Tracker_Artifact_ChangesetValue $changeset_value);
}
