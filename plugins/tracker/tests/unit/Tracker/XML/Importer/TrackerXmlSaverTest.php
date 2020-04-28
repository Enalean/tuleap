<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\XML\Importer;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;

final class TrackerXmlSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    public function testItStoresXmlUsedAsFile(): void
    {
        $tmp_dir = $this->getTmpDir();

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $xml_saver = new TrackerXmlSaver();
        $config    = Mockery::mock(TrackerXmlImportConfig::class);
        $config->shouldReceive('getFileSystemFolder')->once()->andReturn($tmp_dir);
        $config->shouldReceive('getXMLFileName')->once()->andReturn("save_file.xml");
        $xml_saver->storeUsedXmlForTrackersCreation($config, $root);
    }
}
