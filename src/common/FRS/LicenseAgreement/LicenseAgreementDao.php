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

use Project;
use Tuleap\DB\DataAccessObject;

class LicenseAgreementDao extends DataAccessObject
{
    /**
     * @return mixed
     */
    public function getById(Project $project, int $id)
    {
        return $this->getDB()->row(
            <<<EOT
            SELECT *
            FROM frs_download_agreement
            WHERE project_id = ? AND id = ?
            EOT,
            $project->getID(),
            $id
        );
    }

    /**
     * @return mixed
     */
    public function getProjectLicenseAgreements(Project $project)
    {
        return $this->getDB()->run(
            <<<EOT
            SELECT *
            FROM frs_download_agreement
            WHERE project_id = ?
            EOT,
            $project->getID()
        );
    }

    public function isLicenseAgreementValidForProject(Project $project, int $agreement_id): bool
    {
        $row = $this->getDB()->single(
            <<<EOT
            SELECT 1
            FROM frs_download_agreement
            WHERE project_id = ?
            AND id = ?
            EOT,
            [$project->getID(), $agreement_id]
        );
        return $row !== null;
    }

    /**
     * @return mixed
     */
    public function getLicenseAgreementForPackage(\FRSPackage $package)
    {
        return $this->getDB()->row(
            <<<EOT
            SELECT a.*
            FROM frs_download_agreement AS a
                INNER JOIN frs_package_download_agreement a_p ON (a_p.agreement_id = a.id)
            WHERE a_p.package_id = ?
            EOT,
            $package->getPackageID()
        );
    }

    public function saveLicenseAgreementForPackage(\FRSPackage $package, int $license_agreement_id): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($package, $license_agreement_id) {
            $this->resetLicenseAgreementForPackage($package);
            $this->getDB()->run(
                <<<EOT
                INSERT INTO frs_package_download_agreement(agreement_id, package_id)
                VALUES (?, ?)
                EOT,
                $license_agreement_id,
                $package->getPackageID(),
            );
        });
    }

    public function resetLicenseAgreementForPackage(\FRSPackage $package): void
    {
        $this->getDB()->run('DELETE FROM frs_package_download_agreement WHERE package_id = ?', $package->getPackageID());
    }

    public function save(LicenseAgreement $license): void
    {
        $this->getDB()->run(
            <<<EOT
            UPDATE frs_download_agreement
            SET title = ?, content = ?
            WHERE id = ?
            EOT,
            $license->getTitle(),
            $license->getContent(),
            $license->getId(),
        );
    }

    public function create(Project $project, NewLicenseAgreement $license): int
    {
        $this->getDB()->run(
            <<<EOT
            INSERT INTO frs_download_agreement (project_id, title, content)
            VALUES (?, ?, ?)
            EOT,
            $project->getID(),
            $license->getTitle(),
            $license->getContent(),
        );
        return (int) $this->getDB()->lastInsertId();
    }

    /**
     * @return int|false
     */
    public function getDefaultLicenseIdForProject(Project $project)
    {
        return $this->getDB()->single('SELECT agreement_id FROM frs_download_agreement_default WHERE project_id = ?', [$project->getID()]);
    }

    public function setProjectDefault(Project $project, LicenseAgreementInterface $license)
    {
        $this->getDB()->run(
            'REPLACE INTO frs_download_agreement_default(project_id, agreement_id) VALUES (?, ?)',
            $project->getID(),
            $license->getId()
        );
    }

    public function canBeDeleted(Project $project, LicenseAgreementInterface $license_agreement): bool
    {
        $result = $this->getDB()->single(
            <<<EOT
            SELECT 1
            FROM frs_package_download_agreement
            INNER JOIN frs_package fp ON frs_package_download_agreement.package_id = fp.package_id
            WHERE fp.group_id = ?
            AND agreement_id = ?
            AND status_id IN (?, ?)
            LIMIT 1
            EOT,
            [
                $project->getID(),
                $license_agreement->getId(),
                \FRSPackage::STATUS_ACTIVE,
                \FRSPackage::STATUS_HIDDEN
            ]
        );
        return $result === false;
    }

    public function getListOfPackagesForLicenseAgreement(LicenseAgreementInterface $license_agreement): array
    {
        return $this->getDB()->run(
            <<<EOT
            SELECT fp.*
            FROM frs_package_download_agreement
            INNER JOIN frs_package fp ON frs_package_download_agreement.package_id = fp.package_id
            WHERE agreement_id = ?
            AND status_id IN (?, ?)
            EOT,
            $license_agreement->getId(),
            \FRSPackage::STATUS_ACTIVE,
            \FRSPackage::STATUS_HIDDEN
        );
    }

    /**
     * @throws \Throwable
     */
    public function delete(LicenseAgreementInterface $license): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($license) {
            $this->getDB()->run('DELETE FROM frs_download_agreement_default WHERE agreement_id = ?', $license->getId());
            $this->getDB()->run('DELETE FROM frs_package_download_agreement WHERE agreement_id = ?', $license->getId());
            $this->getDB()->run('DELETE FROM frs_download_agreement WHERE id = ?', $license->getId());
        });
    }
}
