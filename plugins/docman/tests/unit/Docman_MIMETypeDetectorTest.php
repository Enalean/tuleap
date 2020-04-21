<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Docman_MIMETypeDetectorTest extends TestCase
{

    public function testItReturnsTheRightOfficeMimeType(): void
    {
        $filename = 'test.docm';
        $detector = new Docman_MIMETypeDetector();

        $this->assertEquals('application/vnd.ms-word.document.macroEnabled.12', $detector->getRightOfficeType($filename));
    }

    public function testItReturnsNullIfTheFileIsNotAnOfficeOne(): void
    {
        $filename = 'image.jpg';
        $detector = new Docman_MIMETypeDetector();

        $this->assertEquals(null, $detector->getRightOfficeType($filename));
    }
}
