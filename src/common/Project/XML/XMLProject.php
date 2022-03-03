<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Project\XML;

use Tuleap\Project\Service\XML\XMLService;

final class XMLProject
{
    /**
     * @var XMLService[]
     * @psalm-readonly
     */
    private array $services = [];

    public function __construct(
        private string $unix_name,
        private string $full_name,
        private string $description,
        private string $access,
    ) {
    }

    public function export(): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><project></project>');
        $xml->addAttribute('unix-name', $this->unix_name);
        $xml->addAttribute('full-name', $this->full_name);
        $xml->addAttribute('description', $this->description);
        $xml->addAttribute('access', $this->access);

        $xml->addChild('long-description');

        $services = $xml->addChild('services');
        if (count($this->services) > 0) {
            foreach ($this->services as $service) {
                $service->export($services);
            }
        }

        return $xml;
    }

    /**
     * @psalm-mutation-free
     */
    public function withService(XMLService $service): self
    {
        $new             = clone $this;
        $new->services[] = $service;

        return $new;
    }
}
