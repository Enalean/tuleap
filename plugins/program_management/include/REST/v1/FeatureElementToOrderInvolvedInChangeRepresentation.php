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

namespace Tuleap\ProgramManagement\REST\v1;

/**
 * @psalm-immutable
 */
final class FeatureElementToOrderInvolvedInChangeRepresentation
{
    public const AFTER  = 'after';
    public const BEFORE = 'before';
    /**
     * @var int[] {@required true} {@min 1}{@max 1}
     */
    public $ids;

    /**
     * @var string {@required true} {@choice after,before}
     */
    public $direction;

    /**
     * @var int {@required true}
     */
    public $compared_to;

    public function __construct(array $ids, string $direction, int $compared_to)
    {
        $this->ids         = $ids;
        $this->direction   = $direction;
        $this->compared_to = $compared_to;
    }
}
