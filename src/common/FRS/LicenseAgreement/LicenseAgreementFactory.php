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
            throw new InvalidLicenseAgreementException('Package doesnt exist yet');
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
    public function getProjectLicenseAgreements(Project $project): array
    {
        $agreements = [];
        foreach ($this->dao->getProjectLicenseAgreements($project) as $row) {
            $agreements []= new LicenseAgreement($row['id'], $row['title'], $row['content']);
        }
        return $agreements;
    }

    public function getDefaultLicenseAgreementForProject(Project $project): LicenseAgreementInterface
    {
        $id = $this->dao->getDefaultLicenseIdForProject($project);
        if ($id === false) {
            if (ForgeConfig::get('sys_frs_license_mandatory')) {
                return new DefaultLicenseAgreement();
            }
            return new NoLicenseToApprove();
        }
        if ($id === NoLicenseToApprove::ID && ForgeConfig::get('sys_frs_license_mandatory')) {
            return new DefaultLicenseAgreement();
        }
        $license = $this->getLicenseAgreementById($project, $id);
        if ($license) {
            return $license;
        }
        return new DefaultLicenseAgreement();
    }

    public function updateLicenseAgreementForPackage(Project $project, FRSPackage $package, int $license_approval_id): void
    {
        if ($license_approval_id === NoLicenseToApprove::ID && ForgeConfig::get('sys_frs_license_mandatory')) {
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

    public function getLicenseAgreementById(Project $project, int $id): ?LicenseAgreementInterface
    {
        if ($id === DefaultLicenseAgreement::ID) {
            return new DefaultLicenseAgreement();
        }
        if ($id === NoLicenseToApprove::ID) {
            return new NoLicenseToApprove();
        }
        $row = $this->dao->getById($project, $id);
        if ($row) {
            return new LicenseAgreement($row['id'], $row['title'], $row['content']);
        }
        return null;
    }

    public function save(Project $project, LicenseAgreementInterface $license): void
    {
        if ($license instanceof NewLicenseAgreement) {
            $this->dao->create($project, $license);
        } elseif ($license instanceof LicenseAgreement) {
            $this->dao->save($license);
        }
    }

    public function setProjectDefault(Project $project, LicenseAgreementInterface $license): void
    {
        $this->dao->setProjectDefault($project, $license);
    }

    public function canBeDeleted(Project $project, LicenseAgreementInterface $license): bool
    {
        if (! $license->isModifiable()) {
            throw new InvalidLicenseAgreementException('Cannot delete a license agreement that cannot be modified');
        }
        return $this->dao->canBeDeleted($project, $license);
    }

    /**
     * @return FRSPackage[]
     */
    public function getListOfPackagesForLicenseAgreement(LicenseAgreementInterface $license): array
    {
        $packages = [];
        foreach ($this->dao->getListOfPackagesForLicenseAgreement($license) as $package_row) {
            $packages []= new FRSPackage($package_row);
        }
        return $packages;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Project $project, LicenseAgreementInterface $license): void
    {
        if (! $this->canBeDeleted($project, $license)) {
            throw new InvalidLicenseAgreementException('Cannot delete a license agreement that is still used');
        }
        $this->dao->delete($license);
    }
}
