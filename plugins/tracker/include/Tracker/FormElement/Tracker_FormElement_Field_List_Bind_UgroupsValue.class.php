<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


class Tracker_FormElement_Field_List_Bind_UgroupsValue extends Tracker_FormElement_Field_List_BindValue
{

    /**
     * @var ProjectUGroup
     */
    protected $ugroup;

    public function __construct($id, ProjectUGroup $ugroup, $is_hidden)
    {
        parent::__construct($id, $is_hidden);
        $this->ugroup = $ugroup;
    }

    public function getLabel()
    {
        return $this->ugroup->getTranslatedName();
    }

    public function getUGroupName()
    {
        return $this->ugroup->getName();
    }

    /**
     *
     * @return int
     */
    public function getUgroupId()
    {
        return $this->ugroup->getId();
    }

    public function __toString()
    {
        return self::class . ' #' . $this->getId();
    }

    /**
     *
     * @return array An array of user names
     */
    public function getMembersName()
    {
        return $this->ugroup->getUsers()->getNames();
    }

    public function getAPIValue()
    {
        return $this->getUGroupName();
    }

    public function getXMLExportLabel()
    {
        return $this->getUGroupName();
    }

    public function getProject()
    {
        return $this->ugroup->getProject();
    }

    public function getUgroup()
    {
        return $this->ugroup;
    }

    public function getFullRESTValue(Tracker_FormElement_Field $field)
    {
        $class_user_representation = '\\Tuleap\\Project\\REST\\UserGroupRepresentation';
        $ugroup_representation     = new $class_user_representation;

        $ugroup_manager = new UGroupManager();
        $project        = $field->getTracker()->getProject();

        $ugroup_representation->build($project->getID(), $ugroup_manager->getById($this->getUgroupId()));
        return $ugroup_representation;
    }

    public function getRESTId()
    {
        $project_id           = $this->getProject()->getID();
        $representation_class = '\\Tuleap\\Project\\REST\\UserGroupRepresentation';
        $user_group_id        = call_user_func_array($representation_class . '::getRESTIdForProject', array($project_id, $this->getUgroupId()));

        return $user_group_id;
    }
}
