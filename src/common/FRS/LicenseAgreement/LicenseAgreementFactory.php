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
use FRSPackage;
use Project;

class LicenseAgreementFactory
{
    /**
     * @var LicenseAgreementDao
     */
    private $dao;

    public function __construct(LicenseAgreementDao $dao)
    {
        $this->dao = $dao;
    }

    public function getLicenseAgreementForPackage(FRSPackage $package): LicenseAgreementInterface
    {
        if ($package->getPackageID() === null) {
            if (! ForgeConfig::get('sys_frs_license_mandatory')) {
                return new NoLicenseToApprove();
            }
            return new DefaultLicenseAgreement();
        }
        if (! $package->getApproveLicense()) {
            return new NoLicenseToApprove();
        }
        $row = $this->dao->getLicenseAgreementForPackage($package);
        if ($row === null) {
            return new DefaultLicenseAgreement();
        }
        return new LicenseAgreement($row['id'], $row['title'], $row['content']);
    }

    /**
     * @return LicenseAgreementInterface[]
     */
    public function getProjectLicenseAgreements(\Project $project): array
    {
        $agreements = [];
        foreach ($this->dao->getProjectLicenseAgreements($project) as $row) {
            $agreements []= new LicenseAgreement($row['id'], $row['title'], $row['content']);
        }
        return $agreements;
    }

    public function updateLicenseAgreementForPackage(Project $project, FRSPackage $package, int $license_approval_id): void
    {
        if ($license_approval_id === NoLicenseToApprove::ID && \ForgeConfig::get('sys_frs_license_mandatory')) {
            throw new InvalidLicenseAgreementException(_('The platform mandates a license agreement, none given'));
        }

        if ($license_approval_id !== NoLicenseToApprove::ID && $license_approval_id !== DefaultLicenseAgreement::ID) {
            if (! $this->dao->isLicenseAgreementValidForProject($project, $license_approval_id)) {
                throw new InvalidLicenseAgreementException(_('The given license agreement is not valid for this project'));
            }
            $this->dao->saveLicenseAgreementForPackage($package, $license_approval_id);
        } else {
            $this->dao->resetLicenseAgreementForPackage($package);
        }

        $package->setApproveLicense(self::convertLicenseAgreementIdToPackageApprovalLicense($license_approval_id));
    }

    public static function convertLicenseAgreementIdToPackageApprovalLicense(int $license_approval_id): bool
    {
        return $license_approval_id !== NoLicenseToApprove::ID;
    }
}
