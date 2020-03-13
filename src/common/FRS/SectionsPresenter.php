<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\FRS;

use Project;
use Tuleap\FRS\LicenseAgreement\Admin\ListLicenseAgreementsController;

class SectionsPresenter
{
    public $project_id;
    public $permissions;
    public $processors;
    public $permissions_url;
    public $processors_url;
    public $license_agreements_url;

    public function __construct(Project $project)
    {
        $this->project_id  = $project->getID();
        $this->permissions = _('Access controls');
        $this->processors  = _('Processors');

        $this->permissions_url = '/file/admin/?' . http_build_query(array(
            'group_id' => $this->project_id,
            'action'   => 'edit-permissions'
        ));
        $this->processors_url = '/file/admin/manageprocessors.php?' . http_build_query(array(
            'group_id' => $this->project_id
        ));
        $this->license_agreements_url = ListLicenseAgreementsController::getUrl($project);
    }
}
