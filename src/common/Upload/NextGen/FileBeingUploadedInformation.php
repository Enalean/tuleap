<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Upload\NextGen;

use Tuleap\Tus\Identifier\FileIdentifier;
use Tuleap\Tus\NextGen\TusFileInformation;

final readonly class FileBeingUploadedInformation implements TusFileInformation
{
    public function __construct(private FileIdentifier $id, private string $name, private int $length, private int $offset)
    {
        if ($this->length < 0) {
            throw new \UnexpectedValueException('The length must be positive');
        }
        if ($this->offset < 0) {
            throw new \UnexpectedValueException('The offset must be positive');
        }
    }

    #[\Override]
    public function getID(): FileIdentifier
    {
        return $this->id;
    }

    #[\Override]
    public function getLength(): int
    {
        return $this->length;
    }

    #[\Override]
    public function getOffset(): int
    {
        return $this->offset;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }
}
