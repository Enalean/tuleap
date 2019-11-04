<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use ServiceFile;

class ToolbarPresenter extends BaseFrsPresenter
{
    /** @var SectionsPresenter */
    public $sections;
    /** @var Project */
    private $project;

    public $title_frs_administration;

    public function __construct(Project $project)
    {
        parent::__construct();

        $this->project  = $project;
        $this->sections = array();

        $this->title_frs_administration = _('Files Administration');
    }

    public function setPermissionIsActive()
    {
        $this->permissions_active = true;
    }

    public function setProcessorsIsActive()
    {
        $this->processors_active = true;
    }

    public function setLicenseAgreementIsActive()
    {
        $this->license_agreements_active = true;
    }

    public function displaySectionNavigation()
    {
        $this->sections = new SectionsPresenter($this->project);
    }
}
