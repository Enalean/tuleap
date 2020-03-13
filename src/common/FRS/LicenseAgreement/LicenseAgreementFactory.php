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
            $agreements[] = new LicenseAgreement($row['id'], $row['title'], $row['content']);
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

    public function save(Project $project, LicenseAgreementInterface $license): LicenseAgreementInterface
    {
        if ($license instanceof NewLicenseAgreement) {
            $id = $this->dao->create($project, $license);
            return new LicenseAgreement($id, $license->getTitle(), $license->getContent());
        }
        if ($license instanceof LicenseAgreement) {
            $this->dao->save($license);
        }
        return $license;
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
            $packages[] = new FRSPackage($package_row);
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

    public function duplicate(\FRSPackageFactory $package_factory, Project $project, Project $template_project, array $packages_mapping)
    {
        $agreement_mapping = $this->duplicateLicenseAgreements($project, $template_project);

        $this->duplicateDefaultLicense($project, $template_project, $agreement_mapping);

        foreach ($packages_mapping as $template_package_id => $target_package_id) {
            $template_package = $package_factory->getFRSPackageFromDb($template_package_id);
            if (! $template_package) {
                continue;
            }
            $target_package   = $package_factory->getFRSPackageFromDb($target_package_id);
            if (! $target_package) {
                continue;
            }
            $template_license = $this->getLicenseAgreementForPackage($template_package);
            $target_license   = $this->getMappedLicense($project, $template_license->getId(), $agreement_mapping);
            if (! $target_license) {
                continue;
            }
            $this->updateLicenseAgreementForPackage($project, $target_package, $target_license->getId());
        }
    }

    private function duplicateLicenseAgreements(Project $project, Project $template_project): array
    {
        $agreement_mapping = [];
        foreach ($this->getProjectLicenseAgreements($template_project) as $template_agreement) {
            $new_agreement = $this->save(
                $project,
                new NewLicenseAgreement($template_agreement->getTitle(), $template_agreement->getContent())
            );
            $agreement_mapping[$template_agreement->getId()] = $new_agreement;
        }
        return $agreement_mapping;
    }

    private function duplicateDefaultLicense(Project $project, Project $template_project, array $agreement_mapping): void
    {
        $default_agreement_template_id = $this->dao->getDefaultLicenseIdForProject($template_project);
        if ($default_agreement_template_id) {
            $license = $this->getMappedLicense($project, $default_agreement_template_id, $agreement_mapping);
            if ($license) {
                $this->setProjectDefault($project, $license);
            }
        }
    }

    private function getMappedLicense(Project $target_project, int $template_agreement_id, array $agreement_mapping): ?LicenseAgreementInterface
    {
        if ($template_agreement_id === NoLicenseToApprove::ID || $template_agreement_id === DefaultLicenseAgreement::ID) {
            $license = $this->getLicenseAgreementById($target_project, $template_agreement_id);
            if ($license) {
                return $license;
            }
        } elseif ($agreement_mapping[$template_agreement_id]) {
            return $agreement_mapping[$template_agreement_id];
        }
        return null;
    }
}
