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

namespace Tuleap\FRS\LicenseAgreement;

use ForgeConfig;

class LicenseAgreementDisplay
{
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $purifier;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;

    public function __construct(\Codendi_HTMLPurifier $purifier, \TemplateRendererFactory $renderer_factory)
    {
        $this->purifier = $purifier;
        $this->renderer = $renderer_factory->getRenderer(__DIR__ . '/template');
    }

    public function getModal(): string
    {
        return $this->renderer->renderToString('license-modal', ['organisation_name' => ForgeConfig::get('sys_org_name'), 'exchange_policy_url' => ForgeConfig::get('sys_exchange_policy_url'), 'contact_email' => ForgeConfig::get('sys_email_contact')]);
    }

    public function show(\FRSPackage $package, int $file_id, string $fname): string
    {
        if (($package->getApproveLicense() == 0) && (isset($GLOBALS['sys_frs_license_mandatory']) && ! $GLOBALS['sys_frs_license_mandatory'])) {
            return '<A HREF="/file/download/' . urlencode((string) $file_id) . '" title="' . $this->purifier->purify($file_id) . " - " . $this->purifier->purify($fname) . '">' . $this->purifier->purify($fname) . '</A>';
        }
        return '<a href="#" data-file-id="' . urlencode((string) $file_id) . '" class="frs-license-agreement-modal-link">'.$this->purifier->purify($fname).'</a>';
    }
}
