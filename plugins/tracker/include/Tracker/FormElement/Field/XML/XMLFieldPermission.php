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

namespace Tuleap\Tracker\FormElement\Field\XML;

final class XMLFieldPermission
{
    private const SCOPE = 'field';
    /**
     * @var string
     * @readonly
     */
    private $field_id_ref;
    /**
     * @var GroupPermission
     * @readonly
     */
    private $permission;

    public function __construct(string $field_id_ref, GroupPermission $permission)
    {
        $this->field_id_ref = $field_id_ref;
        $this->permission   = $permission;
    }

    public function export(\SimpleXMLElement $permissions): \SimpleXMLElement
    {
        $permission = $permissions->addChild('permission');

        $permission->addAttribute('scope', self::SCOPE);
        $permission->addAttribute('REF', $this->field_id_ref);
        $permission->addAttribute('ugroup', $this->permission->ugroup_name);
        $permission->addAttribute('type', $this->permission->type);

        return $permission;
    }
}
