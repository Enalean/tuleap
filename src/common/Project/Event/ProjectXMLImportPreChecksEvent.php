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

namespace Tuleap\Project\Event;

use SimpleXMLElement;
use Tuleap\Event\Dispatchable;

class ProjectXMLImportPreChecksEvent implements Dispatchable
{
    public const NAME = 'projectXMLImportPreChecksEvent';

    /**
     * @var SimpleXMLElement
     * @psalm-readonly
     */
    private $xml_element;

    public function __construct(SimpleXMLElement $xml_element)
    {
        $this->xml_element = $xml_element;
    }

    /**
     * @psalm-mutation-free
     */
    public function getXmlElement(): SimpleXMLElement
    {
        return $this->xml_element;
    }
}
