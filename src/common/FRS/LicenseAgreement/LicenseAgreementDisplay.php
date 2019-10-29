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

class LicenseAgreementDisplay
{
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(\Codendi_HTMLPurifier $purifier)
    {
        $this->purifier = $purifier;
    }

    public function show(\FRSPackage $package, int $file_id, string $fname): string
    {
        if (($package->getApproveLicense() == 0) && (isset($GLOBALS['sys_frs_license_mandatory']) && ! $GLOBALS['sys_frs_license_mandatory'])) {
            return '<A HREF="/file/download/' . urlencode((string) $file_id) . '" title="' . $this->purifier->purify($file_id) . " - " . $this->purifier->purify($fname) . '">' . $this->purifier->purify($fname) . '</A>';
        }
        return '<A HREF="javascript:showConfirmDownload(' . $package->getGroupID() . ',' . $file_id . ')" title="' . $file_id . " - " . $this->purifier->purify($fname) . '">' . $this->purifier->purify($fname) . '</A>';
    }
}
