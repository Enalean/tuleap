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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveBackgroundColorStub implements RetrieveBackgroundColor
{
    private function __construct(private array $colors)
    {
    }

    public static function withColor(string $color_name): self
    {
        return new self([$color_name]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveColors(string $color_name, string ...$other_colors): self
    {
        return new self([$color_name, ...$other_colors]);
    }

    #[\Override]
    public function retrieveBackgroundColor(
        ArtifactIdentifier $artifact_identifier,
        UserIdentifier $user_identifier,
    ): BackgroundColor {
        if (count($this->colors) > 0) {
            return new BackgroundColor(array_shift($this->colors));
        }
        throw new \LogicException('No color name configured');
    }
}
