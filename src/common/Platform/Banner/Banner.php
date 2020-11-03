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
 * @psalm-type BannerImportance = Banner::IMPORTANCE_STANDARD|Banner::IMPORTANCE_WARNING|Banner::IMPORTANCE_CRITICAL
 */
class Banner
{
    public const IMPORTANCE_STANDARD = 'standard';
    public const IMPORTANCE_WARNING  = 'warning';
    public const IMPORTANCE_CRITICAL = 'critical';

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
     * @psalm-param BannerImportance $importance
     */
    public function __construct(string $message, string $importance)
    {
        $this->message    = $message;
        $this->importance = $importance;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @psalm-return BannerImportance
     */
    public function getImportance(): string
    {
        return $this->importance;
    }
}
