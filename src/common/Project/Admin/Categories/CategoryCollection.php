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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\Categories;

use TroveCat;

final class CategoryCollection
{
    /**
     * @psalm-var array<int, TroveCat> $root_categories
     * @psalm-readonly
     */
    private $root_categories = [];

    public function __construct(TroveCat ...$root_categories)
    {
        foreach ($root_categories as $category) {
            $this->root_categories[$category->getId()] = $category;
        }
    }

    /**
     * @psalm-param array<int, list<string>> $submitted_values
     */
    public static function buildFromWebPayload(array $submitted_values): CategoryCollection
    {
        $root_categories = [];
        foreach ($submitted_values as $root_id => $trove_cat_ids) {
            if (! is_array($trove_cat_ids)) {
                continue;
            }
            $category = new TroveCat((int) $root_id, '', '');
            $has_children = false;
            foreach ($trove_cat_ids as $cat_id) {
                if ($cat_id === '') {
                    continue;
                }
                $category->addChildren(new TroveCat((int) $cat_id, '', ''));
                $has_children = true;
            }
            if ($has_children) {
                $root_categories[] = $category;
            }
        }
        return new self(...$root_categories);
    }

    /**
     * @return TroveCat[]
     *
     * @psalm-mutation-free
     */
    public function getRootCategories(): array
    {
        return array_values($this->root_categories);
    }

    /**
     * @psalm-mutation-free
     */
    public function getCategory(TroveCat $category): TroveCat
    {
        return $this->root_categories[$category->getId()];
    }
}
