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

    public function getLicenseAgreementForPackage(\FRSPackage $package): LicenseAgreementInterface
    {
        $row = $this->dao->getLicenseAgreementForPackage($package);
        if ($row === null) {
            return new NullLicenseAgreement();
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
}
