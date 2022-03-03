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

namespace Tuleap\Project\Service\XML;

final class XMLService
{
    private function __construct(private string $shortname, private bool $is_enabled)
    {
    }

    public static function buildEnabled(string $shortname): self
    {
        return new self($shortname, true);
    }

    public static function buildDisabled(string $shortname): self
    {
        return new self($shortname, false);
    }

    public function export(\SimpleXMLElement $xml): void
    {
        $service = $xml->addChild('service');
        $service->addAttribute('shortname', $this->shortname);
        $service->addAttribute('enabled', $this->is_enabled ? '1' : '0');
    }
}
