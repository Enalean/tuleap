<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Stubs\Domain\Document\Section;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\CollectRequiredSectionInformation;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class CollectRequiredSectionInformationStub implements CollectRequiredSectionInformation
{
    private bool $called = false;

    private function __construct(private readonly Ok|Err $result)
    {
    }

    public static function withRequiredInformation(): self
    {
        return new self(Result::ok(null));
    }

    public static function withoutRequiredInformation(): self
    {
        return new self(Result::err(Fault::fromMessage('Required information are missing')));
    }

    public function collectRequiredSectionInformation(ArtidocWithContext $artidoc, int $artifact_id): Ok|Err
    {
        $this->called = true;

        return $this->result;
    }

    public function isCalled(): bool
    {
        return $this->called;
    }
}
