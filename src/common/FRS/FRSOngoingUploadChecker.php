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

class FRSOngoingUploadChecker implements \Tuleap\Event\Dispatchable  //phpcs:ignore
{
    public const NAME = 'frsOngoingUploadChecker';

    /**
     * @var FRSFile
     */
    private $file;
    /**
     * @var bool
     */
    private $is_file_being_uploaded = false;

    public function __construct(\FRSFile $file)
    {
        $this->file = $file;
    }

    public function getFile(): FRSFile
    {
        return $this->file;
    }

    public function isFileBeingUploaded(): bool
    {
        return $this->is_file_being_uploaded;
    }

    public function setIsFileBeingUploadedToTrue(): void
    {
        $this->is_file_being_uploaded = true;
    }
}
