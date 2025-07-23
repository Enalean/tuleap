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

use Tuleap\ProgramManagement\Domain\Workspace\Tracker\VerifyTrackerSemantics;

final class VerifyTrackerSemanticsStub implements VerifyTrackerSemantics
{
    public function __construct(private bool $has_title_semantic, private bool $has_status_semantic)
    {
    }

    public static function withAllSemantics(): self
    {
        return new self(true, true);
    }

    public static function withoutTitleSemantic(): self
    {
        return new self(false, true);
    }

    public static function withoutStatusSemantic(): self
    {
        return new self(true, false);
    }

    #[\Override]
    public function hasTitleSemantic(int $tracker_id): bool
    {
        return $this->has_title_semantic;
    }

    #[\Override]
    public function hasStatusSemantic(int $tracker_id): bool
    {
        return $this->has_status_semantic;
    }
}
