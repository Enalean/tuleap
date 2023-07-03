<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Updater;

use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;

final class MoveChangesetXMLUpdater
{
    public function isChangesetNodeDeletable(SimpleXMLElement $artifact_xml, $index): bool
    {
        return ( count($artifact_xml->changeset[$index]->field_change)) === 0 &&
            (count($artifact_xml->changeset[$index]->comments) === 0) &&
            $index > 0;
    }

    public function deleteChangesetNode(SimpleXMLElement $artifact_xml, $index): void
    {
        unset($artifact_xml->changeset[$index]);
    }

    public function deleteFieldChangeNode(SimpleXMLElement $changeset_xml, $index): void
    {
        unset($changeset_xml->field_change[$index]);
    }

    public function deleteFieldChangeValueNode(SimpleXMLElement $changeset_xml, $index): void
    {
        unset($changeset_xml->field_change[$index]->value);
    }

    public function deleteEmptyCommentsNode(SimpleXMLElement $changeset_xml): void
    {
        $this->deleteEmptyCommentNodes($changeset_xml->comments);

        if ($changeset_xml->comments->comment === null || count($changeset_xml->comments->comment) === 0) {
            unset($changeset_xml->comments);
        }
    }

    public function deleteValueInFieldChangeAtIndex(SimpleXMLElement $changeset_xml, int $field_change_index, int $value_index): void
    {
        unset($changeset_xml->field_change[$field_change_index]->value[$value_index]);
    }

    private function deleteEmptyCommentNodes(SimpleXMLElement $comments_xml): void
    {
        $last_index = $comments_xml->comment === null ? -1 : count($comments_xml->comment) - 1;
        if ($comments_xml->comment === null) {
            return;
        }
        for ($index = $last_index; $index >= 0; $index--) {
            if ((string) $comments_xml->comment[$index]->body === '') {
                unset($comments_xml->comment[$index]);
            }
        }
    }

    public function isFieldChangeCorrespondingToField(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_field,
        $index,
    ): bool {
        $field_change = $changeset_xml->field_change[$index];

        return (string) $field_change['field_name'] === $source_field->getName();
    }

    public function useTargetTrackerFieldName(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $target_field,
        $index,
    ): void {
        $changeset_xml->field_change[$index]['field_name'] = $target_field->getName();
    }

    public function addLastMovedChangesetComment(
        PFUser $current_user,
        SimpleXMLElement $artifact_xml,
        Tracker $source_tracker,
        int $moved_time,
    ): void {
        $last_changeset = $artifact_xml->addChild('changeset');
        if (! $last_changeset instanceof SimpleXMLElement) {
            return;
        }

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insertWithAttributes($last_changeset, 'submitted_by', $current_user->getId(), ['format' => 'id']);
        $cdata->insertWithAttributes(
            $last_changeset,
            'submitted_on',
            date('c', $moved_time),
            ['format' => 'ISO8601']
        );

        $this->addLastChangesetCommentContent($current_user, $last_changeset, $source_tracker, $moved_time);
    }

    private function addLastChangesetCommentContent(
        PFUser $current_user,
        SimpleXMLElement $last_changeset,
        Tracker $source_tracker,
        int $moved_time,
    ): void {
        $comments_tag = $last_changeset->addChild('comments');
        $comment_tag  = $comments_tag->addChild('comment');

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insertWithAttributes($comment_tag, 'submitted_by', $current_user->getId(), ['format' => 'id']);
        $cdata->insertWithAttributes(
            $comment_tag,
            'submitted_on',
            date('c', $moved_time),
            ['format' => 'ISO8601']
        );
        $cdata->insertWithAttributes(
            $comment_tag,
            'body',
            sprintf(
                dgettext('tuleap-tracker', "Artifact was moved from '%s' tracker in '%s' project."),
                $source_tracker->getName(),
                $source_tracker->getProject()->getPublicName()
            ),
            ['format' => 'text']
        );
    }

    public function addSubmittedInformation(SimpleXMLElement $changeset_xml, PFUser $user, int $submitted_on): void
    {
        $changeset_xml->submitted_on           = date('c', $submitted_on);
        $changeset_xml->submitted_by           = $user->getId();
        $changeset_xml->submitted_by['format'] = 'id';
    }
}
