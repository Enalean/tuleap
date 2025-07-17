<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\ArtifactLink;

use PFUser;
use Tracker_Artifact_ChangesetValue_List;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkProject;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkStatusValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkValue;
use Tuleap\Color\ColorName;
use Tuleap\Option\Option;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\RetrieveTypeFromShortname;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\REST\Artifact\ArtifactReferenceWithType;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Tracker;

final readonly class ArtifactLinkFieldWithValueBuilder implements BuildArtifactLinkFieldWithValue
{
    public function __construct(
        private PFUser $current_user,
        private RetrieveSemanticTitleField $title_field_retriever,
        private RetrieveTypeFromShortname $from_shortname_type_retriever,
    ) {
    }

    public function buildArtifactLinkFieldWithValue(
        ConfiguredField $configured_field,
        ?ArtifactLinkChangesetValue $changeset_value,
    ): ArtifactLinkFieldWithValue {
        if ($changeset_value === null) {
            return new ArtifactLinkFieldWithValue(
                $configured_field->field->getLabel(),
                $configured_field->display_type,
                [],
            );
        }

        $all_links = $changeset_value->getFullRESTValue($this->current_user);

        $links = [];
        foreach ($all_links->links as $forward_link) {
            assert($forward_link instanceof ArtifactReferenceWithType);
            $type_presenter = $this->from_shortname_type_retriever->getFromShortname($forward_link->type);
            if ($type_presenter === null) {
                continue;
            }
            $linked_artifact = $forward_link->getArtifact();
            $linked_tracker  = $linked_artifact->getTracker();
            $links[]         = new ArtifactLinkValue(
                $this->renameLinkTypes($type_presenter)->forward_label,
                $linked_tracker->getItemName(),
                $linked_tracker->getColor(),
                $this->getLinkProject($linked_tracker->getProject()),
                $linked_artifact->getId(),
                $this->getArtifactTitle($linked_tracker, $linked_artifact),
                $linked_artifact->getUri(),
                $this->getArtifactStatus($linked_tracker, $linked_artifact),
            );
        }

        foreach ($all_links->reverse_links as $reverse_link) {
            assert($reverse_link instanceof ArtifactReferenceWithType);
            $type_presenter = $this->from_shortname_type_retriever->getFromShortname($reverse_link->type);
            if ($type_presenter === null) {
                continue;
            }
            $linked_artifact = $reverse_link->getArtifact();
            $linked_tracker  = $linked_artifact->getTracker();
            $links[]         = new ArtifactLinkValue(
                $this->renameLinkTypes($type_presenter)->reverse_label,
                $linked_tracker->getItemName(),
                $linked_tracker->getColor(),
                $this->getLinkProject($linked_tracker->getProject()),
                $linked_artifact->getId(),
                $this->getArtifactTitle($linked_tracker, $linked_artifact),
                $linked_artifact->getUri(),
                $this->getArtifactStatus($linked_tracker, $linked_artifact),
            );
        }

        return new ArtifactLinkFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            $links,
        );
    }

    private function getArtifactTitle(Tracker $tracker, Artifact $artifact): string
    {
        $title_field = $this->title_field_retriever->fromTracker($tracker);
        if ($title_field === null) {
            return '';
        }

        $value = $artifact->getLastChangeset()?->getValue($title_field);
        return $value?->getValue() ?? '';
    }

    /**
     * @return Option<ArtifactLinkStatusValue>
     */
    private function getArtifactStatus(Tracker $tracker, Artifact $artifact): Option
    {
        $semantic_status = TrackerSemanticStatus::load($tracker);
        $status_field    = $semantic_status->getField();
        if ($status_field === null) {
            return Option::nothing(ArtifactLinkStatusValue::class);
        }

        $values = $artifact->getLastChangeset()?->getValue($status_field);
        if (! ($values instanceof Tracker_Artifact_ChangesetValue_List)) {
            return Option::nothing(ArtifactLinkStatusValue::class);
        }

        if ($values->count() === 0) {
            return Option::nothing(ArtifactLinkStatusValue::class);
        }

        $value      = array_values($values->getListValues())[0];
        $decorators = $status_field->getDecorators();
        return Option::fromValue(
            new ArtifactLinkStatusValue(
                $value->getLabel(),
                isset($decorators[$value->getId()])
                    ? Option::fromValue(ColorName::fromName($decorators[$value->getId()]->getCurrentColor()))
                    : Option::nothing(ColorName::class),
                $semantic_status->isOpen($artifact),
            )
        );
    }

    private function renameLinkTypes(TypePresenter $presenter): TypePresenter
    {
        if ($presenter instanceof TypeIsChildPresenter) {
            $presenter->forward_label = dgettext('tuleap-artidoc', 'is Parent of');
            $presenter->reverse_label = dgettext('tuleap-artidoc', 'is Child of');
            return $presenter;
        }

        if ($presenter->shortname === '') {
            $presenter->forward_label = dgettext('tuleap-artidoc', 'is Linked to');
            $presenter->reverse_label = dgettext('tuleap-artidoc', 'is Linked to');
            return $presenter;
        }

        return $presenter;
    }

    private function getLinkProject(\Project $linked_project): ArtifactLinkProject
    {
        return new ArtifactLinkProject(
            (int) $linked_project->getID(),
            $linked_project->getPublicName(),
            EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($linked_project->getIconUnicodeCodepoint())
        );
    }
}
