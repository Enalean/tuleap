<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

/**
 * @psalm-immutable
 */
final class PossibleParentSelectorPresenter
{
    public int $create_new_parent_value = \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::CREATE_NEW_PARENT_VALUE;

    /**
     * @var PossibleParentCategoryPresenter[]
     */
    public array $possible_parents_categories;

    public function __construct(public string $parent_label, public string $form_name_prefix, public bool $can_create, PossibleParentCategoryPresenter ...$possible_parents_categories)
    {
        $this->possible_parents_categories = $possible_parents_categories;
    }
}
