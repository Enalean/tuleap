<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Updater_TemporaryFileXMLUpdaterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Updater_TemporaryFileXMLUpdater */
    private $updater;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    protected function setUp(): void
    {
        $this->artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
                . '<artifact>'
                . '  <file id="fileinfo_1">'
                . '    <filename>toto.txt</filename>'
                . '    <path>/path/to/toto.txt</path>'
                . '  </file>'
                . '  <file id="fileinfo_2">'
                . '    <filename>Spec.doc</filename>'
                . '    <path>/path/to/Spec.doc</path>'
                . '  </file>'
                . '</artifact>');

        $temporary_file_creator = \Mockery::spy(\Tracker_XML_Updater_TemporaryFileCreator::class);
        $temporary_file_creator->shouldReceive('createTemporaryFile')->with('/path/to/toto.txt')->andReturns('/tmp/toto.txt');
        $temporary_file_creator->shouldReceive('createTemporaryFile')->with('/path/to/Spec.doc')->andReturns('/tmp/Spec.doc');

        $this->updater = new Tracker_XML_Updater_TemporaryFileXMLUpdater($temporary_file_creator);
    }

    public function testItReplacesThePathWithTheNewTemporaryPath(): void
    {
        $this->updater->update($this->artifact_xml);

        $this->assertEquals('/tmp/toto.txt', (string) $this->artifact_xml->file[0]->path);
        $this->assertEquals('/tmp/Spec.doc', (string) $this->artifact_xml->file[1]->path);
    }
}
