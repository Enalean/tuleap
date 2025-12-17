<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AI\Mistral;

/**
 * @psalm-immutable
 */
final readonly class TokenUsage
{
    public int $prompt_tokens;
    public int $total_tokens;
    public int $completion_tokens;

    public static function fromFakeValues(): self
    {
        $self                    = new self();
        $self->prompt_tokens     = 0;
        $self->total_tokens      = 0;
        $self->completion_tokens = 0;
        return $self;
    }

    public static function fromValues(int $prompt_token, int $total_token, int $completion_token): self
    {
        $self                    = new self();
        $self->prompt_tokens     = $prompt_token;
        $self->total_tokens      = $total_token;
        $self->completion_tokens = $completion_token;
        return $self;
    }
}
