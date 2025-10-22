<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 * Copyright Enalean (c) 2019-present. All rights reserved.
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

use Tuleap\GlobalLanguageMock;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HTML_Element_SelectboxTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    use GlobalLanguageMock;

    public function testWithNone(): void
    {
        $selectbox = new HTML_Element_Selectbox('label', 'name', 'value', true);
        $selectbox->setId('id-none-test');
        $this->assertEquals(
            '<select id="id-none-test" class="tlp-select tlp-select-adjusted" data-test="selectbox" name="name" ><option value="" >-- None --</option></select>',
            $selectbox->renderValue()
        );
    }

    public function testSetId(): void
    {
        $selectbox = new HTML_Element_Selectbox('label', 'name', 'value', false);
        $selectbox->setId('id');
        $this->assertEquals('<select id="id" class="tlp-select tlp-select-adjusted" data-test="selectbox" name="name" ></select>', $selectbox->renderValue());
    }

    public function testAddMultipleOptions(): void
    {
        $selectbox = new HTML_Element_Selectbox('label', 'name', 'value', false);
        $selectbox->setId('id-add-options-test');
        $selectbox->addMultipleOptions(['one' => '1', 'two' => '2'], 'two');
        $this->assertEquals(
            '<select id="id-add-options-test" class="tlp-select tlp-select-adjusted" data-test="selectbox" name="name" ><option value="one" >1</option><option value="two" selected="selected">2</option></select>',
            $selectbox->renderValue()
        );
    }
}
