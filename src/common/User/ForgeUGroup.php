<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\User\UserGroup\NameTranslator;

class User_ForgeUGroup implements User_UGroup // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    private $id;

    private $name;

    private $description;

    public function __construct($id, $name, $description)
    {
        $this->id          = $id;
        $this->name        = NameTranslator::getUserGroupDisplayName($name);
        $this->description = $description;
    }

    #[\Override]
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getName(): string
    {
        return (string) $this->name;
    }
}
