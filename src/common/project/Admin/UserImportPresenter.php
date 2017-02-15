<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin;

use Codendi_HTMLPurifier;

class UserImportPresenter
{
    public $project_id;
    public $upload_file;
    public $title;
    public $submit;
    public $import_welcome;

    public function __construct($project_id)
    {
        $this->project_id     = $project_id;
        $this->import_welcome = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText(
                'project_admin_userimport',
                'import_welcome',
                array('/project/admin/userimport.php?group_id='.$this->project_id.'&mode=showformat&func=import')
            ),
            CODENDI_PURIFIER_LIGHT
        );

        $this->upload_file = $GLOBALS['Language']->getText('project_admin_userimport', 'upload_file');
        $this->title       = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText(
                'project_admin_userimport',
                'import_members',
                array(help_button('project-admin.html#adding-removing-users'))
            ),
            CODENDI_PURIFIER_LIGHT
        );

        $this->submit = _('Load users');
    }
}
