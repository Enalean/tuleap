<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\Upload;

use Tuleap\Docman\Tus\TusFileInformation;

final class DocumentAlreadyUploadedInformation implements TusFileInformation
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $length;

    public function __construct(int $id, int $length)
    {
        $this->id = $id;
        if ($length < 0) {
            throw new \UnexpectedValueException('The length must be positive');
        }
        $this->length = $length;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getOffset(): int
    {
        return $this->length;
    }
}
