<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValuePermissionsOnArtifactFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValuePermissionsOnArtifactRepresentation;

/**
 * Manage values in changeset for date fields
 */
class Tracker_Artifact_ChangesetValue_PermissionsOnArtifact extends Tracker_Artifact_ChangesetValue
{

    /**
     * @var array
     */
    protected $perms;
    protected $used;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $used, $perms)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->perms = $perms;
        $this->used = $used;
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitPermissionsOnArtifact($this);
    }

    /**
     * Get the permissions
     *
     * @return Array the permissions
     */
    public function getPerms()
    {
        return $this->perms;
    }

    /**
     * @return array
     */
    public function getUgroupNamesFromPerms()
    {
        $ugroup_names = array();

        foreach ($this->perms as $ugroup_id) {
            $ugroup_names[] = $this->getUgroupName($ugroup_id);
        }

        return $ugroup_names;
    }

    /**
     * Return the value of used
     *
     * @return bool true if the permissions are used
     */
    public function getUsed()
    {
        return $this->used;
    }

    public function getRESTValue(PFUser $user)
    {
        $representation = new ArtifactFieldValuePermissionsOnArtifactRepresentation();
        $representation->build(
            $this->field->getId(),
            $this->field->getLabel(),
            array_map(
                array($this, 'getUserGroupRESTId'),
                $this->getPerms()
            ),
            array_map(
                array($this, 'getUgroupRESTRepresentation'),
                $this->getPerms()
            )
        );
        return $representation;
    }

    protected function getUserGroupRESTId($user_group_id)
    {
        $project_id = $this->getField()->getTracker()->getProject()->getID();

        return UserGroupRepresentation::getRESTIdForProject($project_id, $user_group_id);
    }

    public function getFullRESTValue(PFUser $user)
    {
        $representation = new ArtifactFieldValuePermissionsOnArtifactFullRepresentation();
        $representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            array_map(
                array($this, 'getUgroupLabel'),
                $this->getPerms()
            )
        );
        return $representation;
    }

    /**
     * Returns the value of this changeset value (human readable)
     *
     * @return string The value of this artifact changeset value for the web interface
     */
    public function getValue()
    {
        return '';
    }

    /**
     * Returns diff between current perms and perms in param
     *
     * @return string|false The difference between another $changeset_value, false if no differneces
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        $previous = $changeset_value->getPerms();
        $next = $this->getPerms();
        $changes = false;
        if ($previous !== $next) {
            $removed_elements = array_diff($previous, $next);
            $removed_arr = array();
            foreach ($removed_elements as $removed_element) {
                $removed_arr[] = $this->getUgroupLabel($removed_element);
            }
            $removed = $this->format(implode(', ', $removed_arr), $format);
            $added_elements = array_diff($next, $previous);
            $added_arr = array();
            foreach ($added_elements as $added_element) {
                $added_arr[] = $this->getUgroupLabel($added_element);
            }
            $added   = $this->format(implode(', ', $added_arr), $format);
            if (empty($next)) {
                $changes = ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'cleared');
            } elseif (empty($previous)) {
                $changes = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'set_to') . ' ' . $added;
            } elseif (count($previous) == 1 && count($next) == 1) {
                $changes = ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'changed_from') . ' ' . $removed . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'to') . ' ' . $added;
            } else {
                if ($removed) {
                    $changes = $removed . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'removed');
                }
                if ($added) {
                    if ($changes) {
                        $changes .= PHP_EOL;
                    }
                    $changes .= $added . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'added');
                }
            }
        }
        return $changes;
    }

    public function nodiff($format = 'html')
    {
        $next = $this->getPerms();
        $added_arr = array();
        foreach ($next as $element) {
                $added_arr[] = $this->getUgroupLabel($element);
        }
        $added = $this->format(implode(', ', $added_arr), $format);
        return ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'set_to') . ' ' . $added;
    }

    private function format($value, $format)
    {
        if ($format === 'text') {
            return $value;
        }
        return Codendi_HTMLPurifier::instance()->purify($value);
    }

    protected function getDao()
    {
        return new UGroupDao(CodendiDataAccess::instance());
    }

    private function getUgroupName($ugroup_id)
    {
        $row = $this->getDao()->searchByUGroupId($ugroup_id)->getRow();
        return $row['name'];
    }

    protected function getUgroupLabel($ugroup_id)
    {
        return util_translate_name_ugroup($this->getUgroupName($ugroup_id));
    }

    protected function getUgroupRESTRepresentation($u_group_id)
    {
        $ugroup_manager = new UGroupManager($this->getDao());
        $u_group        = $ugroup_manager->getById($u_group_id);

        $classname_with_namespace = 'Tuleap\Project\REST\UserGroupRepresentation';
        $representation           = new $classname_with_namespace;

        $representation->build($this->getField()->getTracker()->getProject()->getID(), $u_group);

        return $representation;
    }
}
