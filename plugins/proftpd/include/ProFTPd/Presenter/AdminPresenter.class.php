<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd\Presenter;

use Tuleap\ProFTPd\Admin\PermissionsManager;
use ProjectUGroup;

class AdminPresenter
{
    /** @var int */
    private $project_id;

    /** @var ProjectUGroup[] */
    private $static_ugroups;

    /** @var int */
    private $readers_ugroup_id;

    /** @var int */
    private $writers_ugroup_id;

    /** @var string */
    private $admin_ugroup_url;

    public function __construct($project_id, array $static_ugroups, $readers_ugroup_id, $writers_ugroup_id)
    {
        $this->project_id        = $project_id;
        $this->static_ugroups    = $static_ugroups;
        $this->readers_ugroup_id = $readers_ugroup_id;
        $this->writers_ugroup_id = $writers_ugroup_id;
        $this->admin_ugroup_url  = '/project/admin/ugroup.php?' . http_build_query(array('group_id' => $this->project_id));
    }

    public function project_id()
    {
        return $this->project_id;
    }

    public function admin_action()
    {
        return PROFTPD_BASE_URL . '/';
    }

    public function readers_selector()
    {
        return array(
            'name'    => 'permissions[' . PermissionsManager::PERM_READ . ']',
            'options' => $this->getSelectorOptions($this->readers_ugroup_id)
        );
    }

    public function writers_selector()
    {
        return array(
            'name'    => 'permissions[' . PermissionsManager::PERM_WRITE . ']',
            'options' => $this->getSelectorOptions($this->writers_ugroup_id)
        );
    }

    private function getSelectorOptions($selected)
    {
        $options = array($this->getNoneOption($selected));
        foreach ($this->static_ugroups as $group) {
            $options[] = array(
                'value'    => $group->getId(),
                'label'    => $group->getTranslatedName(),
                'selected' => $selected == $group->getId()
            );
        }
        return $options;
    }

    private function getNoneOption($selected)
    {
        return array(
            'value'    => ProjectUGroup::NONE,
            'label'    => $GLOBALS['Language']->getText('global', 'none'),
            'selected' => $selected == ProjectUGroup::NONE,
        );
    }

    public function some_usergroups_exists()
    {
        return count($this->static_ugroups) > 0;
    }

    public function submit()
    {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function admin_title()
    {
        return dgettext('tuleap-proftpd', '(S)FTP administration');
    }

    public function permissions_title()
    {
        return dgettext('tuleap-proftpd', 'Permissions');
    }

    public function readers_header()
    {
        return dgettext('tuleap-proftpd', 'Readers');
    }

    public function writers_header()
    {
        return dgettext('tuleap-proftpd', 'Writers');
    }

    public function permissions_pitch()
    {
        return dgettext('tuleap-proftpd', 'Select the user group that will be allowed to access and/or' .
            ' modify content in the FTP area. Please note that Writers will automatically have read access as well.');
    }

    public function permissions_create_modify()
    {
        return sprintf(
            dgettext('tuleap-proftpd', 'You can also <a href="%1$s">create or modify</a>
                user groups in project administration.'),
            $this->admin_ugroup_url
        );
    }

    public function permissions_create()
    {
        return sprintf(
            dgettext('tuleap-proftpd', 'No user group defined yet ! You should <a href="%1$s">create</a> some in project administration.'),
            $this->admin_ugroup_url
        );
    }
}
