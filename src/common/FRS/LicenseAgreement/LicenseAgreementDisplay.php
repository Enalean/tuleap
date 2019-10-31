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
    /**
     * @var LicenseAgreementDao
     */
    private $dao;

    public function __construct(\Codendi_HTMLPurifier $purifier, \TemplateRendererFactory $renderer_factory, LicenseAgreementDao $dao)
    {
        $this->purifier = $purifier;
        $this->renderer = $renderer_factory->getRenderer(__DIR__ . '/template');
        $this->dao      = $dao;
    }

    public function getModals(\Project $project): string
    {
        $custom_agreements = [];
        foreach ($this->dao->getProjectLicenseAgreements($project) as $row) {
            $custom_agreements[] = [
                'id'      => $row['id'],
                'title'   => $row['title'],
                'content' => $this->purifier->purify($row['content'], CODENDI_PURIFIER_FULL),
            ];
        }
        return $this->renderer->renderToString(
            'license-modal',
            [
                'organisation_name'   => ForgeConfig::get('sys_org_name'),
                'exchange_policy_url' => ForgeConfig::get('sys_exchange_policy_url'),
                'contact_email'       => ForgeConfig::get('sys_email_contact'),
                'custom_agreements'   => $custom_agreements,
            ]
        );
    }

    public function show(\FRSPackage $package, int $file_id, string $fname): string
    {
        $display_filename = $this->purifier->purify($fname);
        if ($package->getApproveLicense() == 0 && ! ForgeConfig::get('sys_frs_license_mandatory')) {
            return '<a href="/file/download/' . urlencode((string) $file_id) . '" title="' . $this->purifier->purify($file_id) . " - " . $display_filename . '">' . $display_filename . '</a>';
        }
        $agreement_id = $this->dao->getLicenseAgreementForPackage($package);
        if ($agreement_id === false) {
            $agreement_id = '0';
        }
        return sprintf('<a href="#" class="frs-license-agreement-modal-link" data-file-id="%d" data-agreement-id="%d">%s</a>', $file_id, $agreement_id, $this->purifier->purify($fname));
    }
}
