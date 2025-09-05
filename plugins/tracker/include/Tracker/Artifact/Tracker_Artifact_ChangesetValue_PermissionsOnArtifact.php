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

use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValuePermissionsOnArtifactFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValuePermissionsOnArtifactRepresentation;

/**
 * Manage values in changeset for date fields
 */
class Tracker_Artifact_ChangesetValue_PermissionsOnArtifact extends Tracker_Artifact_ChangesetValue
{
    /**
     * @var array<int, string>
     */
    private array $perms;
    protected $used;

    /**
     * @param array<int, string> $perms
     */
    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $used, array $perms)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->perms = $perms;
        $this->used  = $used;
    }

    /**
     * @return mixed
     */
    #[\Override]
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitPermissionsOnArtifact($this);
    }

    /**
     * Get the permissions
     *
     * @return int[] the permissions
     */
    public function getPerms(): array
    {
        return array_keys($this->perms);
    }

    /**
     * @return string[]
     */
    public function getUgroupNamesFromPerms(): array
    {
        return array_values($this->perms);
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

    #[\Override]
    public function getRESTValue(PFUser $user)
    {
        $representation = new ArtifactFieldValuePermissionsOnArtifactRepresentation();
        $representation->build(
            $this->field->getId(),
            $this->field->getLabel(),
            array_map(
                [$this, 'getUserGroupRESTId'],
                $this->getPerms()
            ),
            array_map(
                [$this, 'getUgroupRESTRepresentation'],
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

    #[\Override]
    public function getFullRESTValue(PFUser $user)
    {
        $representation = new ArtifactFieldValuePermissionsOnArtifactFullRepresentation();
        $representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            array_map(
                [$this, 'getUgroupLabel'],
                $this->getUgroupNamesFromPerms()
            ),
            array_map(
                [$this, 'getUserGroupRESTId'],
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
    #[\Override]
    public function getValue()
    {
        return '';
    }

    /**
     * Returns diff between current perms and perms in param
     *
     * @return string|false The difference between another $changeset_value, false if no differneces
     */
    #[\Override]
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        assert($changeset_value instanceof self);
        $previous = $changeset_value->getPerms();
        $next     = $this->getPerms();
        $changes  = false;
        if ($previous !== $next) {
            $removed_elements = array_diff($previous, $next);
            $removed_arr      = [];
            foreach ($removed_elements as $removed_element_id) {
                $removed_arr[] = $this->getUgroupLabel($changeset_value->perms[$removed_element_id]);
            }
            $removed        = $this->format(implode(', ', $removed_arr), $format);
            $added_elements = array_diff($next, $previous);
            $added_arr      = [];
            foreach ($added_elements as $added_element_id) {
                $added_arr[] = $this->getUgroupLabel($this->perms[$added_element_id]);
            }
            $added = $this->format(implode(', ', $added_arr), $format);
            if (empty($next)) {
                $changes = ' ' . dgettext('tuleap-tracker', 'cleared');
            } elseif (empty($previous)) {
                $changes = dgettext('tuleap-tracker', 'set to') . ' ' . $added;
            } elseif (count($previous) == 1 && count($next) == 1) {
                $changes = ' ' . dgettext('tuleap-tracker', 'changed from') . ' ' . $removed . ' ' . dgettext('tuleap-tracker', 'to') . ' ' . $added;
            } else {
                if ($removed) {
                    $changes = $removed . ' ' . dgettext('tuleap-tracker', 'removed');
                }
                if ($added) {
                    if ($changes) {
                        $changes .= PHP_EOL;
                    }
                    $changes .= $added . ' ' . dgettext('tuleap-tracker', 'added');
                }
            }
        }
        return $changes;
    }

    #[\Override]
    public function nodiff($format = 'html')
    {
        $added_arr = [];
        foreach ($this->perms as $ugroup_name) {
                $added_arr[] = $this->getUgroupLabel($ugroup_name);
        }
        $added = $this->format(implode(', ', $added_arr), $format);
        return ' ' . dgettext('tuleap-tracker', 'set to') . ' ' . $added;
    }

    private function format($value, $format)
    {
        if ($format === 'text') {
            return $value;
        }
        return Codendi_HTMLPurifier::instance()->purify($value);
    }

    protected function getDao(): UGroupDao
    {
        return new UGroupDao();
    }

    protected function getUgroupLabel(string $ugroup_name): string
    {
        return \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey($ugroup_name);
    }

    protected function getUgroupRESTRepresentation($u_group_id)
    {
        $ugroup_manager = new UGroupManager($this->getDao());
        $u_group        = $ugroup_manager->getById($u_group_id);
        return new MinimalUserGroupRepresentation($this->getField()->getTracker()->getProject()->getID(), $u_group);
    }
}
