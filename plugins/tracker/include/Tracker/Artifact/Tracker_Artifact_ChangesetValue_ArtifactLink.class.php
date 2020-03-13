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

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueArtifactLinksFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;

/**
 * Manage values in changeset for 'artifact link' fields
 */
class Tracker_Artifact_ChangesetValue_ArtifactLink extends Tracker_Artifact_ChangesetValue
{

    /**
     * @var array of artifact_id => Tracker_ArtifactLinkInfo
     */
    protected $artifact_links;

    /**
     * @var array of artifact_id => Tracker_ArtifactLinkInfo
     */
    protected $reverse_artifact_links;

    /** @var UserManager */
    private $user_manager;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $artifact_links, $reverse_artifact_links)
    {
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
    public function hasChanges($new_value)
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

            if ($array_cur_values[$id]->getNature() !== $artifactlinkinfo->getNature()) {
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
     *
     *
     * @return Tracker_Artifact_ChangesetValue_ArtifactLinkDiff
     */
    public function getArtifactLinkInfoDiff(Tracker $tracker, ?Tracker_Artifact_ChangesetValue_ArtifactLink $old_changeset_value = null)
    {
        $previous = array();
        if ($old_changeset_value !== null) {
            $previous = $old_changeset_value->getValue();
        }
        return $this->getArtifactLinkDiff($previous, $this->getValue(), $tracker);
    }

    protected function getArtifactLinkDiff($previous, $next, Tracker $tracker)
    {
        return new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff(
            $previous,
            $next,
            $tracker,
            $this->getNaturePresenterFactory()
        );
    }

    /** @protected for testing purpose */
    protected function getNaturePresenterFactory()
    {
        return new NaturePresenterFactory(new NatureDao(), new ArtifactLinksUsageDao());
    }


    /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff($format = 'html')
    {
        $next = $this->getValue();
        if (!empty($next)) {
            $result = '';
            $added_arr = array();
            foreach ($next as $art_id => $added_element) {
                $added_arr[] = $added_element->getLink();
            }
            $added   = implode(', ', $added_arr);
            $result = ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'set_to') . ' ' . $added;
            return $result;
        }
    }

    public function getRESTValue(PFUser $user)
    {
        return $this->getFullRESTValue($user);
    }

    public function getFullRESTValue(PFUser $user)
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

    private function getAllOutgoingArtifactIdsUserCanSee(PFUser $user)
    {
        $values = array();

        foreach ($this->getArtifactIdsUserCanSee($user) as $id) {
            $values[] = $this->buildArtifactReference($id);
        }

        return $values;
    }

    private function getAllIncomingArtifactIdsUserCanSee(PFUser $user)
    {
        $values = array();

        foreach ($this->getIncomingArtifactIdsUserCanSee($user) as $id) {
            $values[] = $this->buildArtifactReference($id);
        }

        return $values;
    }

    private function buildArtifactReference($artifact_id)
    {
        $tracker_artifact_factory = Tracker_ArtifactFactory::instance();
        $artifact_reference       = new ArtifactReference();
        $artifact_reference->build($tracker_artifact_factory->getArtifactById($artifact_id));

        return $artifact_reference;
    }

    /**
     * Returns the value of this changeset value
     *
     * @return mixed The value of this artifact changeset value
     */
    public function getValue()
    {
        return $this->artifact_links;
    }

    public function getArtifactIds()
    {
        return array_keys($this->artifact_links);
    }

    /**
     * Returns the list of artifact id in all artifact links user can see
     *
     * @return type
     */
    public function getArtifactIdsUserCanSee(PFUser $user)
    {
        $artifact_links_user_can_see = array();

        foreach ($this->artifact_links as $artifact_id => $link) {
            if ($link->userCanView($user)) {
                $artifact_links_user_can_see[] = $artifact_id;
            }
        }

        return $artifact_links_user_can_see;
    }

    private function getIncomingArtifactIdsUserCanSee(PFUser $user)
    {
        $reverse_artifact_links_user_can_see = array();

        foreach ($this->reverse_artifact_links as $artifact_id => $link) {
            if ($link->userCanView($user)) {
                $reverse_artifact_links_user_can_see[] = $artifact_id;
            }
        }

        return $reverse_artifact_links_user_can_see;
    }

    private function setCurrentUserIfUserIsNotDefined(&$user)
    {
        if (! isset($user)) {
            $user = $this->user_manager->getCurrentUser();
        }
    }
}
