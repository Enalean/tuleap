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

namespace Tuleap\Taskboard\REST\v1\Cell;

use Tuleap\AgileDashboard\REST\v1\OrderRepresentation;
use Tuleap\REST\I18NRestException;

/**
 * @psalm-immutable
 */
final class CellPatchRepresentation
{
    /**
     * @var int | null $add {@type int} {@required false}
     */
    public $add;
    /**
     * @var OrderRepresentation | null $order {@type \Tuleap\AgileDashboard\REST\v1\OrderRepresentation} {@required false}
     */
    public $order;

    /**
     * @throws I18NRestException 400
     */
    public function checkIsValid(): void
    {
        if ($this->order === null && $this->add === null) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-taskboard', "Please specify 'add' and/or 'order' in the payload.")
            );
        }
    }

    private function __construct(?int $add, ?OrderRepresentation $order_representation)
    {
        $this->add   = $add;
        $this->order = $order_representation;
    }

    public static function build(?int $add, ?OrderRepresentation $order_representation): self
    {
        return new self($add, $order_representation);
    }
}
