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

namespace Tuleap\Date;

/**
 * @psalm-immutable
 */
final class TlpRelativeDatePresenter
{
    /**
     * @var string
     */
    public $date;
    /**
     * @var string
     */
    public $absolute_date;
    /**
     * @var string
     */
    public $placement;
    /**
     * @var string
     */
    public $preference;
    /**
     * @var string
     */
    public $locale;

    public function __construct(
        string $date,
        string $absolute_date,
        string $placement,
        string $preference,
        string $locale,
    ) {
        $this->date          = $date;
        $this->absolute_date = $absolute_date;
        $this->placement     = $placement;
        $this->preference    = $preference;
        $this->locale        = $locale;
    }
}
