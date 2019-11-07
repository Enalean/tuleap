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

use Tuleap\DB\DataAccessObject;

class LicenseAgreementDao extends DataAccessObject
{
    /**
     * @return mixed
     */
    public function getById(\Project $project, int $id)
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
    public function getProjectLicenseAgreements(\Project $project)
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

    public function isLicenseAgreementValidForProject(\Project $project, int $agreement_id): bool
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
        $this->getDB()->run(
            <<<EOT
            REPLACE INTO frs_package_download_agreement(agreement_id, package_id)
            VALUES (?, ?)
            EOT,
            $license_agreement_id,
            $package->getPackageID(),
        );
    }

    public function resetLicenseAgreementForPackage(\FRSPackage $package): void
    {
        $this->getDB()->run('DELETE FROM frs_package_download_agreement WHERE package_id = ?', $package->getPackageID());
    }

    public function save(LicenseAgreementInterface $license): void
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
}
