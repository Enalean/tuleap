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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Feature\CheckIsValidFeature;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class CheckIsValidFeatureStub implements CheckIsValidFeature
{
    private function __construct(private bool $is_valid, private bool $is_visible)
    {
    }

    #[\Override]
    public function checkIsFeature(int $feature_id, UserIdentifier $user): void
    {
        if (! $this->is_valid) {
            throw new FeatureIsNotPlannableException($feature_id);
        }
        if (! $this->is_visible) {
            throw new FeatureNotFoundException($feature_id);
        }
    }

    public static function withAlwaysValidFeatures(): self
    {
        return new self(true, true);
    }

    public static function withNotValid(): self
    {
        return new self(false, true);
    }

    public static function withNotVisible(): self
    {
        return new self(true, false);
    }
}
