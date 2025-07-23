<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Tests\Stub;

use Tuleap\Docman\Version\IRetrieveVersion;
use Tuleap\Docman\Version\VersionNotFoundException;

class IRetrieveVersionStub implements IRetrieveVersion
{
    private function __construct(private ?\Docman_Version $version)
    {
    }

    public static function withoutVersion(): self
    {
        return new self(null);
    }

    public static function withVersion(\Docman_Version $version): self
    {
        return new self($version);
    }

    #[\Override]
    public function getVersion(int $id): \Docman_Version
    {
        if ($this->version) {
            return $this->version;
        }

        throw new VersionNotFoundException();
    }
}
