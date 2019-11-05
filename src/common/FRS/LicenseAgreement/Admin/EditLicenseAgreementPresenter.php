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

use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;

/**
 * @psalm-immutable
 */
class EditLicenseAgreementPresenter
{
    /**
     * @var string
     */
    public $save_url;
    /**
     * @var string
     */
    public $list_url;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $content;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(\Project $project, LicenseAgreementInterface $license_agreement, \CSRFSynchronizerToken $csrf_token)
    {
        $this->title = $license_agreement->getTitle();
        $this->content = $license_agreement->getContent();
        $this->list_url = ListLicenseAgreementsController::getUrl($project);
        $this->save_url = SaveLicenseAgreementController::getUrl($project, $license_agreement);
        $this->csrf_token = $csrf_token;
    }
}
