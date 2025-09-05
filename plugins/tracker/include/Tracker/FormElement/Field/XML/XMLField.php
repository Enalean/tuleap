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

use Tuleap\Tracker\FormElement\FieldNameFormatter;
use Tuleap\Tracker\FormElement\XML\XMLFormElement;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;

abstract class XMLField extends XMLFormElement
{
    /**
     * @var XMLFieldPermission[]
     * @readonly
     */
    private array $permissions = [];
    /**
     * @readonly
     */
    private bool $without_permissions_authorized = false;

    final public function __construct(string|IDGenerator $id, string $name)
    {
        parent::__construct($id, static::getType(), $name);
    }

    abstract public static function getType(): string;

    public static function fromTrackerAndName(XMLTracker $tracker, string $name): static
    {
        return new static(FieldNameFormatter::getFormattedName($tracker->item_name . '_' . $name), FieldNameFormatter::getFormattedName($name));
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withPermissions(GroupPermission ...$group_permissions): self
    {
        $new = clone $this;
        foreach ($group_permissions as $permission) {
            $new->permissions[] = new XMLFieldPermission($this->id, $permission);
        }
        return $new;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withoutPermissions(): self
    {
        $new                                 = clone $this;
        $new->without_permissions_authorized = true;
        return $new;
    }

    #[\Override]
    public function exportPermissions(\SimpleXMLElement $form_elements): void
    {
        if (count($this->permissions) === 0 && ! $this->without_permissions_authorized) {
            throw new XMLFieldWithoutPermissionsException($this->name . ' field has no permissions');
        }
        foreach ($this->permissions as $permission) {
            $permission->export($form_elements);
        }
    }
}
