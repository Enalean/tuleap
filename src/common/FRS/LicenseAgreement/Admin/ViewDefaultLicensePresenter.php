<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\LicenseAgreement\Admin;

use ForgeConfig;

class ViewDefaultLicensePresenter
{
    /**
     * @readonly
     * @var string
     */
    public $organisation_name;
    /**
     * @readonly
     * @var string
     */
    public $exchange_policy_url;
    /**
     * @readonly
     * @var string
     */
    public $contact_email;
    /**
     * @readonly
     * @var string
     */
    public $list_url;

    public function __construct(\Project $project)
    {
        $this->organisation_name    = ForgeConfig::get('sys_org_name');
        $this->exchange_policy_url  = ForgeConfig::get('sys_exchange_policy_url');
        $this->contact_email        = ForgeConfig::get('sys_email_contact');
        $this->list_url = ListLicenseAgreementsController::getUrl($project);
    }
}
