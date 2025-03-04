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

namespace Tuleap\Tracker\FormElement\XML;

use function PHPUnit\Framework\assertFalse;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLFormElementImplTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItUseItTrueIsTheDefaultThereforeNoNeedToAddIt(): void
    {
        $form_element = (new XMLFormElementImpl('id', \Tracker_FormElementFactory::FIELD_STRING_TYPE, 'name'))
            ->withUseIt(true);

        $xml = $form_element->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertFalse(isset($xml['use_it']));
    }

    public function testItDoesntExportEmptyDescription(): void
    {
        $form_element = (new XMLFormElementImpl('id', \Tracker_FormElementFactory::FIELD_STRING_TYPE, 'name'));

        $xml = $form_element->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertFalse(isset($xml->description));
    }
}
