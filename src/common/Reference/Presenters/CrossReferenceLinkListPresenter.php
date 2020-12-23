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

/**
 * @psalm-immutable
 */
class CrossReferenceLinkListPresenter
{
    /**
     * @var string
     */
    public $classes_key;
    /**
     * @var string
     */
    public $image;
    /**
     * @var CrossReferenceLinkPresenter[]
     */
    public $cross_ref_link_collection = [];
    /**
     * @var string
     */
    public $icon_message;
    /**
     * @var string
     */
    public $icon_path;

    /**
     * @param CrossReferenceLinkPresenter[] $cross_ref_link_collection
     */
    public function __construct(string $classes_key, string $icon_message, string $icon_path, array $cross_ref_link_collection)
    {
        $this->classes_key               = $classes_key;
        $this->cross_ref_link_collection = $cross_ref_link_collection;
        $this->icon_message              = $icon_message;
        $this->icon_path                 = $icon_path;
    }
}
