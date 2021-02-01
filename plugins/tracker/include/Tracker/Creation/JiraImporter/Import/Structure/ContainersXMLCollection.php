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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use SimpleXMLElement;
use Tuleap\Tracker\XML\IDGenerator;

class ContainersXMLCollection
{
    /**
     * @var array<string, SimpleXMLElement>
     */
    private $containers = [];
    /**
     * @var IDGenerator
     */
    private $field_id_generator;

    public function __construct(IDGenerator $field_id_generator)
    {
        $this->field_id_generator = $field_id_generator;
    }

    public function addContainerInCollection(string $name, SimpleXMLElement $node): void
    {
        $this->containers[$name] = $node;
    }

    /**
     * @throws ContainerNotFoundInCollectionException
     */
    public function getContainerByName(string $name): SimpleXMLElement
    {
        if (! isset($this->containers[$name])) {
            throw new ContainerNotFoundInCollectionException($name);
        }

        return $this->containers[$name];
    }

    public function getNextId(): int
    {
        return $this->field_id_generator->getNextId();
    }
}
