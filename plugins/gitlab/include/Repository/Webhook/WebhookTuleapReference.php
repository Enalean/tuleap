<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

use Tuleap\Tracker\Artifact\Closure\ClosingKeyword;

/**
 * @psalm-immutable
 */
final class WebhookTuleapReference
{
    public function __construct(private int $id, private ?ClosingKeyword $closing_keyword)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getClosingKeyword(): ?ClosingKeyword
    {
        return $this->closing_keyword;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
