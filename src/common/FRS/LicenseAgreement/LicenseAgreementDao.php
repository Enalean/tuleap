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

final class LicenseAgreementDao extends DataAccessObject
{
    public function getProjectLicenseAgreements(\Project $project): array
    {
        return $this->getDB()->run(
            <<<EOT
            SELECT a.*
            FROM frs_package AS p
                INNER JOIN frs_package_download_agreement AS a_p ON (a_p.package_id = p.package_id)
                INNER JOIN frs_download_agreement AS a ON (a.id = a_p.agreement_id)
            WHERE p.group_id = ?
            EOT,
            $project->getID()
        );
    }

    public function getLicenseAgreementIdForPackage(\FRSPackage $package)
    {
        return $this->getDB()->single(
            <<<EOT
            SELECT agreement_id
            FROM frs_package_download_agreement
            WHERE package_id = ?
            EOT,
            [$package->getPackageID()]
        );
    }

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
}
