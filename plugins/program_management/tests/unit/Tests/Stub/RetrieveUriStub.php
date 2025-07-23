<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;

final class RetrieveUriStub implements \Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveUri
{
    private function __construct(private string $uri)
    {
    }

    public static function withDefault(): self
    {
        return new self('/plugins/tracker/?aid=1');
    }

    public static function withId(int $id): self
    {
        return new self('/plugins/tracker/?aid=' . $id);
    }

    #[\Override]
    public function getUri(TimeboxIdentifier $timebox_identifier): string
    {
        return $this->uri;
    }
}
