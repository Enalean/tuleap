<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress\Administration;

class SemanticProgressIntroductionPresenter
{
    /**
     * @var string
     */
    public $semantic_usages_description;
    /**
     * @var bool
     */
    public $is_semantic_defined;
    /**
     * @var string
     */
    public $current_configuration_description;

    public function __construct(
        string $semantic_usages_description,
        bool $is_semantic_defined,
        string $current_configuration_description,
    ) {
        $this->semantic_usages_description       = $semantic_usages_description;
        $this->is_semantic_defined               = $is_semantic_defined;
        $this->current_configuration_description = $current_configuration_description;
    }
}
