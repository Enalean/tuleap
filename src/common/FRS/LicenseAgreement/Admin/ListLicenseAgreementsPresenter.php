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

use CSRFSynchronizerToken;

class ListLicenseAgreementsPresenter
{
    /**
     * @readonly
     * @var int
     */
    public $project_id;
    /**
     * @readonly
     * @var LicenseAgreementPresenter[]
     */
    public $license_agreements;
    /**
     * @readonly
     * @var string
     */
    public $create_url;
    /**
     * @readonly
     * @var string
     */
    public $set_default_agreement_url;

    /**
     * @readonly
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(\Project $project, CSRFSynchronizerToken $csrf_token, LicenseAgreementPresenter ...$license_agreements)
    {
        $this->project_id = (int) $project->getID();
        $this->license_agreements = $license_agreements;
        $this->create_url = AddLicenseAgreementController::getUrl($project);
        $this->set_default_agreement_url = SetDefaultLicenseAgreementController::getUrl($project);
        $this->csrf_token = $csrf_token;
    }
}
