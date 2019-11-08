<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column;

use Cardwall_Column;
use ColorHelper;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenter;

final class ColumnPresenter
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $color;

    /**
     * @var bool
     */
    public $is_collapsed;

    /**
     * @var TrackerMappingPresenter[]
     */
    public $mappings = [];

    public function __construct(Cardwall_Column $column, bool $is_collapsed, array $mappings)
    {
        $this->id           = (int) $column->getId();
        $this->label        = $column->getLabel();
        $this->color        = ($column->isHeaderATLPColor())
            ? $column->getHeadercolor()
            : ColorHelper::CssRGBToHexa($column->getHeadercolor());
        $this->mappings     = $mappings;
        $this->is_collapsed = $is_collapsed;
    }
}
