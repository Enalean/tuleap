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

use Docman_Item;
use Tuleap\Docman\Version\IDeleteVersion;

class IDeleteVersionStub implements IDeleteVersion
{
    private bool $has_been_called = false;

    private function __construct(private bool $success)
    {
    }

    public static function willSucceed(): self
    {
        return new self(true);
    }

    public static function willFail(): self
    {
        return new self(false);
    }

    #[\Override]
    public function deleteSpecificVersion(Docman_Item $item, int $number): bool
    {
        $this->has_been_called = true;

        return $this->success;
    }

    public function hasBeenCalled(): bool
    {
        return $this->has_been_called;
    }
}
