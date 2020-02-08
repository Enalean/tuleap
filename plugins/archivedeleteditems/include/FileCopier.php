<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 */

namespace Tuleap\ArchiveDeletedItems;

use Psr\Log\LoggerInterface;

class FileCopier
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    public function copy($source_file, $destination_file, $skip_duplicated)
    {
        if (! file_exists($source_file)) {
            $this->logger->error("Source file $source_file already exist");
            return false;
        }
        $this->logger->debug("Permissions of source file: " . $this->convertFilePermissionsToNumbers($source_file));
        $this->logger->debug("Owner of source: " . $this->getGroupNameForFile($source_file));

        if (file_exists($destination_file)) {
            if ($skip_duplicated) {
                $this->logger->warning("Destination file $destination_file already exists. Skipping");
                return true;
            } else {
                $this->logger->error("Destination file $destination_file already exists");
                return false;
            }
        }

        if (! touch($destination_file)) {
            $this->logger->error("Can not create destination file $destination_file");
            return false;
        }

        if (! chmod($destination_file, 0640)) {
            unlink($destination_file);
            $this->logger->error("Can not set rights on the destination file $destination_file");
            return false;
        }

        if (! copy($source_file, $destination_file)) {
            unlink($destination_file);
            $this->logger->error("Was not able to copy $source_file to $destination_file");
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    private function convertFilePermissionsToNumbers($path)
    {
        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * @return string
     */
    private function getGroupNameForFile($file)
    {
        $stat  = stat($file);
        $group = posix_getpwuid($stat['uid']);

        return $group['name'];
    }
}
