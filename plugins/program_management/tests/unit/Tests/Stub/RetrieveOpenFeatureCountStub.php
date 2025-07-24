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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveOpenFeatureCount;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class RetrieveOpenFeatureCountStub implements RetrieveOpenFeatureCount
{
    private function __construct(private int $open_feature_count)
    {
    }

    public static function withValue(int $open_feature_count): self
    {
        return new self($open_feature_count);
    }

    #[\Override]
    public function retrieveOpenFeaturesCount(ProgramIdentifier ...$program_identifiers): int
    {
        return $this->open_feature_count;
    }
}
