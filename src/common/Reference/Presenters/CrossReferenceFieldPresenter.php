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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference\Presenters;

class CrossReferenceFieldPresenter
{
    /**
     * @var bool
     */
    public $condensed;
    /**
     * @var string
     */
    public $classes;
    /**
     * @var CrossReferenceByNaturePresenter[]
     */
    public $cross_refs_by_nature = [];
    /**
     * @var bool
     */
    public $with_links;
    /**
     * @var bool
     */
    public $display_params;
    /**
     * @var string
     */
    public $message_legend;

    /**
     * @param CrossReferenceByNaturePresenter[] $cross_refs_by_nature_presenter_collection
     */
    public function __construct(
        bool $condensed,
        bool $with_links,
        bool $display_params,
        array $cross_refs_by_nature_presenter_collection,
    ) {
        $this->condensed            = $condensed;
        $this->classes              = "nature ";
        $this->classes             .= $condensed ? "" : "not-condensed";
        $this->with_links           = $with_links;
        $this->display_params       = $display_params;
        $this->message_legend       = $GLOBALS['Language']->getText('cross_ref_fact_include', 'legend');
        $this->cross_refs_by_nature = $cross_refs_by_nature_presenter_collection;
    }
}
