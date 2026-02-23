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

use Tuleap\Option\Option;
use Tuleap\Request\NotFoundException;

class FileReference extends \Reference
{
    /**
     * @param Option<int> $reference_value
     */
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
        private readonly Option $reference_value,
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
    }

    #[\Override]
    public function getLink(): string
    {
        return $this->reference_value->match(
            function (int $file_id): string {
                $file_factory = new \FRSFileFactory();
                $file         = $file_factory->getFRSFileFromDb($file_id);
                if (! $file) {
                    throw new NotFoundException(self::class . ': no valid file found for ' . $file_id);
                }
                $package_factory = new \FRSPackageFactory();
                $package         = $package_factory->getFRSPackageByFileIdFromDb($file_id);
                if (! $package) {
                    throw new NotFoundException(self::class . ': no valid package found for ' . $file_id);
                }
                if (! $package->getApproveLicense()) {
                    return '/file/download/' . urlencode((string) $file->getFileID());
                }
                return '/file/shownotes.php?release_id=' . urlencode((string) $file->getReleaseID());
            },
            fn (): string => '/file/download/$1',
        );
    }
}
