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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields;

use Tracker_FormElement_Field;

/**
 * @template F of Tracker_FormElement_Field
 */
final class FieldData
{
    /**
     * @psalm-var F
     */
    private $field;

    /**
     * @psalm-param F $field
     */
    public function __construct(Tracker_FormElement_Field $field)
    {
        $this->field = $field;
    }

    /**
     * @psalm-mutation-free
     */
    public function getId(): int
    {
        return (int) $this->field->getId();
    }

    public function userCanSubmit(\PFUser $user): bool
    {
        return $this->field->userCanSubmit($user);
    }

    public function userCanUpdate(\PFUser $user): bool
    {
        return $this->field->userCanUpdate($user);
    }

    /**
     * @psalm-return F
     */
    public function getFullField(): Tracker_FormElement_Field
    {
        return $this->field;
    }
}
