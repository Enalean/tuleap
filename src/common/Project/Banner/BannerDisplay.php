<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Banner;

/**
 * @psalm-immutable
 */
final class BannerDisplay
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var bool
     */
    private $is_visible;

    private function __construct(string $message, bool $is_visible)
    {
        $this->message    = $message;
        $this->is_visible = $is_visible;
    }

    public static function buildHiddenBanner(string $message): self
    {
        return new self($message, false);
    }

    public static function buildVisibleBanner(string $message): self
    {
        return new self($message, true);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isVisible(): bool
    {
        return $this->is_visible;
    }
}
