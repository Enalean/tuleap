<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\LFS\Batch\Request;

class BatchRequestOperation
{
    const UPLOAD_OPERATION   = 'upload';
    const DOWNLOAD_OPERATION = 'download';

    /**
     * @var string
     */
    private $operation;

    public function __construct($operation)
    {
        if ($operation !== self::DOWNLOAD_OPERATION && $operation !== self::UPLOAD_OPERATION) {
            throw new IncorrectlyFormattedBatchRequestException(
                'operation should either be ' . self::UPLOAD_OPERATION . ' or ' . self::DOWNLOAD_OPERATION
            );
        }
        $this->operation = $operation;
    }

    /**
     * @return bool
     */
    public function isDownload()
    {
        return $this->operation === self::DOWNLOAD_OPERATION;
    }

    /**
     * @return bool
     */
    public function isUpload()
    {
        return $this->operation === self::UPLOAD_OPERATION;
    }
}
