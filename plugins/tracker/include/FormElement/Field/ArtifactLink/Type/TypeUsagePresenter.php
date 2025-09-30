<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

class TypeUsagePresenter
{
    /**
     * @var TypePresenter
     */
    public $type;

    /**
     * @var bool
     */
    public $is_or_has_been_used;

    /**
     * @var bool
     */
    public $can_be_deleted;

    public function __construct(TypePresenter $type, $is_or_has_been_used)
    {
        $this->type                = $type;
        $this->is_or_has_been_used = $is_or_has_been_used;
        $this->computeCanBeDeleted();
    }

    public function setIsUsed($is_used)
    {
        $this->is_or_has_been_used = $is_used;
        $this->computeCanBeDeleted();
    }

    private function computeCanBeDeleted()
    {
        $this->can_be_deleted = ! $this->type->is_system && ! $this->is_or_has_been_used;
    }
}
