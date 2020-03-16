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

namespace Tuleap\FRS;

use Tuleap\Request\NotFoundException;

class FileReference extends \Reference
{
    private $reference_value;

    public function __construct(
        $myid,
        $mykeyword,
        $mydescription,
        $mylink,
        $myscope,
        $myservice_short_name,
        $nature,
        $myis_active,
        $mygroup_id,
        $reference_value
    ) {
        parent::__construct(
            $myid,
            $mykeyword,
            $mydescription,
            $mylink,
            $myscope,
            $myservice_short_name,
            $nature,
            $myis_active,
            $mygroup_id,
        );
        $this->reference_value = $reference_value;
    }

    public function getLink()
    {
        if (! $this->reference_value) {
            throw new NotFoundException(self::class . ': no reference value found for ' . $this->reference_value);
        }
        $file_factory = new \FRSFileFactory();
        $file = $file_factory->getFRSFileFromDb($this->reference_value);
        if (! $file) {
            throw new NotFoundException(self::class . ': no valid file found for ' . $this->reference_value);
        }
        $package_factory = new \FRSPackageFactory();
        $package = $package_factory->getFRSPackageByFileIdFromDb($this->reference_value);
        if (! $package) {
            throw new NotFoundException(self::class . ': no valid package found for ' . $this->reference_value);
        }
        if (! $package->getApproveLicense()) {
            return '/file/download/' . urlencode((string) $file->getFileID());
        }
        return '/file/shownotes.php?release_id=' . urlencode((string) $file->getReleaseID());
    }
}
