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

namespace Tuleap\TestManagement\Step\Definition\Field\XML;

use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLStepDefinitionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItsExportedAsAnExternalField(): void
    {
        $field = new StepDefinition(
            100,
            120,
            0,
            'steps',
            'Steps',
            'Some stuff to do',
            true,
            null,
            false,
            false,
            1,
        );

        $xml_field = (new XMLStepDefinition('F100', 'steps'))->fromFormElement($field);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $xml_field->export($xml);

        assertTrue(isset($xml->externalField));
        assertEquals('ttmstepdef', (string) $xml->externalField['type']);
        assertEquals('steps', (string) $xml->externalField->name);
        assertEquals('Steps', (string) $xml->externalField->label);
    }
}
