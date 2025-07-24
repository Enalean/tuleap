<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureTitle;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveFeatureTitleStub implements RetrieveFeatureTitle
{
    private function __construct(private array $titles)
    {
    }

    public static function withTitle(string $title): self
    {
        return new self([$title]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveTitles(string $title, string ...$titles): self
    {
        return new self([$title, ...$titles]);
    }

    public static function withNotVisibleTitle(): self
    {
        return new self([]);
    }

    #[\Override]
    public function getFeatureTitle(FeatureIdentifier $feature_identifier, UserIdentifier $user_identifier): ?string
    {
        if (count($this->titles) > 0) {
            return array_shift($this->titles);
        }
        return null;
    }
}
