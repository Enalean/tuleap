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
     * @var LicenseAgreementFactory
     */
    private $factory;

    public function __construct(\Codendi_HTMLPurifier $purifier, \TemplateRendererFactory $renderer_factory, LicenseAgreementFactory $factory)
    {
        $this->purifier = $purifier;
        $this->renderer = $renderer_factory->getRenderer(__DIR__ . '/template');
        $this->factory = $factory;
    }

    public function getModals(\Project $project): string
    {
        $custom_agreements = [];
        foreach ($this->factory->getProjectLicenseAgreements($project) as $license_agreement) {
            $custom_agreements[] = [
                'id'      => $license_agreement->getId(),
                'title'   => $license_agreement->getTitle(),
                'content' => $this->purifier->purify($license_agreement->getContent(), CODENDI_PURIFIER_FULL),
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

    public function getDownloadLink(\FRSPackage $package, int $file_id, string $fname): string
    {
        $display_filename = $this->purifier->purify($fname);
        if (! $package->getApproveLicense() && ! ForgeConfig::get('sys_frs_license_mandatory')) {
            return '<a href="/file/download/' . urlencode((string) $file_id) . '" title="' . $this->purifier->purify($file_id) . " - " . $display_filename . '">' . $display_filename . '</a>';
        }
        $license_agreement = $this->factory->getLicenseAgreementForPackage($package);
        return sprintf('<a href="#" class="frs-license-agreement-modal-link" data-file-id="%d" data-agreement-id="%d">%s</a>', $file_id, $license_agreement->getId(), $this->purifier->purify($fname));
    }

    public function getPackageEditSelector(\FRSPackage $package, \Project $project): string
    {
        $all_custom_agreements = $this->factory->getProjectLicenseAgreements($project);
        if ($package->getPackageID() === null) {
            $package_agreement = $this->factory->getDefaultLicenseAgreementForProject($project);
        } else {
            $package_agreement = $this->factory->getLicenseAgreementForPackage($package);
        }
        $license_selector      = [];
        if (ForgeConfig::get('sys_frs_license_mandatory') == 1) {
            if (count($all_custom_agreements) === 0) {
                return '<input type="hidden" name="package[approve_license]" value="1">';
            }
        } else {
            $license_selector[] = (new NoLicenseToApprove())->getLicenseOptionPresenter($package_agreement);
        }

        $license_selector[] = (new DefaultLicenseAgreement())->getLicenseOptionPresenter($package_agreement);
        foreach ($all_custom_agreements as $agreement) {
            $license_selector[] = $agreement->getLicenseOptionPresenter($package_agreement);
        }
        return $this->renderer->renderToString(
            EditPackagePresenter::TEMPLATE,
            $license_selector,
        );
    }
}
