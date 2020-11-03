<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Platform\Banner;

/**
 * @psalm-immutable
 * @psalm-import-type BannerImportance from \Tuleap\Platform\Banner\Banner
 */
final class BannerDisplay
{
    /**
     * @var string
     */
    private $message;
    /**
     * @var string
     * @psalm-var BannerImportance
     */
    private $importance;
    /**
     * @var bool
     */
    private $is_visible;

    /**
     * @psalm-param BannerImportance $importance
     */
    private function __construct(string $message, string $importance, bool $is_visible)
    {
        $this->message    = $message;
        $this->is_visible = $is_visible;
        $this->importance = $importance;
    }

    /**
     * @psalm-param BannerImportance $importance
     */
    public static function buildHiddenBanner(string $message, string $importance): self
    {
        return new self($message, $importance, false);
    }

    /**
     * @psalm-param BannerImportance $importance
     */
    public static function buildVisibleBanner(string $message, string $importance): self
    {
        return new self($message, $importance, true);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isVisible(): bool
    {
        return $this->is_visible;
    }

    /**
     * @psalm-return BannerImportance
     */
    public function getImportance(): string
    {
        return $this->importance;
    }
}
