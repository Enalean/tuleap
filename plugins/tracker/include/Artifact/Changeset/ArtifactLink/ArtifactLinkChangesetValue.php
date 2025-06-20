<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\ArtifactLink;

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_ArtifactLinkDiff;
use Tracker_Artifact_ChangesetValueVisitor;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueArtifactLinksFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReferenceWithType;
use Tuleap\Tracker\Tracker;
use Tuleap\User\ProvideCurrentUser;
use UserManager;

/**
 * Manage values in changeset for 'artifact link' fields
 */
class ArtifactLinkChangesetValue extends Tracker_Artifact_ChangesetValue
{
    /** @var array<int, Tracker_ArtifactLinkInfo> */
    private array $artifact_links;
    /** @var array<int, Tracker_ArtifactLinkInfo> */
    private array $reverse_artifact_links;
    private ProvideCurrentUser $user_manager;

    public function __construct(
        int $id,
        Tracker_Artifact_Changeset $changeset,
        ArtifactLinkField $field,
        bool $has_changed,
        array $artifact_links,
        array $reverse_artifact_links,
    ) {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->artifact_links         = $artifact_links;
        $this->reverse_artifact_links = $reverse_artifact_links;
        $this->user_manager           = UserManager::instance();
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitArtifactLink($this);
    }

    /**
     * Check if there are changes between current and new value
     *
     * @param array $new_value array of artifact ids
     *
     * @return bool true if there are differences
     */
    public function hasChanges($new_value): bool
    {
        if (empty($new_value['list_of_artifactlinkinfo']) && empty($new_value['removed_values'])) {
            // no changes
            return false;
        }

        $array_new_values = $new_value['list_of_artifactlinkinfo'];
        $array_cur_values = $this->getValue();
        if (count($array_new_values) !== count($array_cur_values)) {
            return true;
        }

        foreach ($array_new_values as $id => $artifactlinkinfo) {
            if (! isset($array_cur_values[$id])) {
                return true;
            }

            if ($array_cur_values[$id]->getType() !== $artifactlinkinfo->getType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a diff between current changeset value and changeset value in param
     *
     * @return string|false The difference between another $changeset_value, false if no differences
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        $this->setCurrentUserIfUserIsNotDefined($user);
        $diff = $this->getArtifactLinkInfoDiff($this->getField()->getTracker(), $changeset_value);

        return $diff->fetchFormatted($user, $format, $ignore_perms);
    }

    /**
     * Return diff between 2 changeset values
     */
    public function getArtifactLinkInfoDiff(Tracker $tracker, ?ArtifactLinkChangesetValue $old_changeset_value = null): Tracker_Artifact_ChangesetValue_ArtifactLinkDiff
    {
        $previous = [];
        if ($old_changeset_value !== null) {
            $previous = $old_changeset_value->getValue();
        }
        return $this->getArtifactLinkDiff($previous, $this->getValue(), $tracker);
    }

    protected function getArtifactLinkDiff($previous, $next, Tracker $tracker): Tracker_Artifact_ChangesetValue_ArtifactLinkDiff
    {
        return new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff(
            $previous,
            $next,
            $tracker,
            $this->getTypePresenterFactory()
        );
    }

    /** @protected for testing purpose */
    protected function getTypePresenterFactory(): TypePresenterFactory
    {
        return new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao());
    }

    /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff($format = 'html')
    {
        $next = $this->getValue();
        if (! empty($next)) {
            $result    = '';
            $added_arr = [];
            foreach ($next as $art_id => $added_element) {
                $added_arr[] = $added_element->getLink();
            }
            $added  = implode(', ', $added_arr);
            $result = ' ' . dgettext('tuleap-tracker', 'set to') . ' ' . $added;
            return $result;
        }
    }

    public function getRESTValue(PFUser $user): ArtifactFieldValueArtifactLinksFullRepresentation
    {
        return $this->getFullRESTValue($user);
    }

    public function getFullRESTValue(PFUser $user): ArtifactFieldValueArtifactLinksFullRepresentation
    {
        $outgoing_links = $this->getAllOutgoingArtifactIdsUserCanSee($user);
        $incoming_links = $this->getAllIncomingArtifactIdsUserCanSee($user);

        $artifact_links_representation = new ArtifactFieldValueArtifactLinksFullRepresentation();
        $artifact_links_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $outgoing_links,
            $incoming_links
        );

        return $artifact_links_representation;
    }

    /**
     * @return ArtifactReferenceWithType[]
     */
    private function getAllOutgoingArtifactIdsUserCanSee(PFUser $user): array
    {
        $values = [];

        foreach ($this->getLinksUserCanSee($user) as $link_info) {
            $values[] = $this->buildArtifactReference($link_info);
        }

        return array_filter($values);
    }

    /**
     * @return ArtifactReferenceWithType[]
     */
    private function getAllIncomingArtifactIdsUserCanSee(PFUser $user): array
    {
        $values = [];

        foreach ($this->getIncomingLinksUserCanSee($user) as $link_info) {
            $values[] = $this->buildArtifactReference($link_info);
        }

        return array_filter($values);
    }

    private function buildArtifactReference(Tracker_ArtifactLinkInfo $link_info): ?ArtifactReferenceWithType
    {
        $artifact = $link_info->getArtifact();
        if ($artifact === null) {
            return null;
        }
        return ArtifactReferenceWithType::buildWithType(
            $artifact,
            $link_info->getType()
        );
    }

    /**
     * Returns the value of this changeset value
     *
     * @return array<int, Tracker_ArtifactLinkInfo> The value of this artifact changeset value
     */
    public function getValue(): array
    {
        return $this->artifact_links;
    }

    public function getArtifactIds(): array
    {
        return array_keys($this->artifact_links);
    }

    /**
     * Returns the list of artifact id in all artifact links user can see
     *
     * @return Tracker_ArtifactLinkInfo[]
     */
    private function getLinksUserCanSee(PFUser $user): array
    {
        $artifact_links_user_can_see = [];

        foreach ($this->artifact_links as $link) {
            if ($link->userCanView($user)) {
                $artifact_links_user_can_see[] = $link;
            }
        }

        return $artifact_links_user_can_see;
    }

    /**
     * @return Tracker_ArtifactLinkInfo[]
     */
    private function getIncomingLinksUserCanSee(PFUser $user): array
    {
        $reverse_artifact_links_user_can_see = [];

        foreach ($this->reverse_artifact_links as $link) {
            if ($link->userCanView($user)) {
                $reverse_artifact_links_user_can_see[] = $link;
            }
        }

        return $reverse_artifact_links_user_can_see;
    }

    private function setCurrentUserIfUserIsNotDefined(&$user): void
    {
        if (! isset($user)) {
            $user = $this->user_manager->getCurrentUser();
        }
    }
}
