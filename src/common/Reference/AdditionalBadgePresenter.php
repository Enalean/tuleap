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

namespace Tuleap\Reference;

/**
 * @psalm-immutable
 */
final class AdditionalBadgePresenter
{
    private const STATE_PRIMARY   = 'primary';
    private const STATE_SECONDARY = 'secondary';
    private const STATE_DANGER    = 'danger';
    private const STATE_SUCCESS   = 'success';

    /**
     * @var string
     */
    public $label;
    /**
     * @var bool
     */
    public $is_secondary;
    /**
     * @var bool
     */
    public $is_primary;
    /**
     * @var bool
     */
    public $is_danger;
    /**
     * @var bool
     */
    public $is_success;
    /**
     * @var bool
     */
    public $is_plain;

    /**
     * @psalm-param "primary" | "secondary" | "danger" | "success" $state
     */
    private function __construct(string $label, string $state, bool $is_plain)
    {
        $this->label        = $label;
        $this->is_secondary = $state === self::STATE_SECONDARY;
        $this->is_primary   = $state === self::STATE_PRIMARY;
        $this->is_danger    = $state === self::STATE_DANGER;
        $this->is_success   = $state === self::STATE_SUCCESS;
        $this->is_plain     = $is_plain;
    }

    public static function buildPrimary(string $label): self
    {
        return new self($label, self::STATE_PRIMARY, false);
    }

    public static function buildPrimaryPlain(string $label): self
    {
        return new self($label, self::STATE_PRIMARY, true);
    }

    public static function buildSecondary(string $label): self
    {
        return new self($label, self::STATE_SECONDARY, false);
    }

    public static function buildDanger(string $label): self
    {
        return new self($label, self::STATE_DANGER, false);
    }

    public static function buildSuccess(string $label): self
    {
        return new self($label, self::STATE_SUCCESS, false);
    }
}
