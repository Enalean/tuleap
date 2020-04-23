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

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\ForgeConfigSandbox;

final class TrackerXmlSaverTest extends TestCase
{
    use ForgeConfigSandbox;
    use MockeryPHPUnitIntegration;

    public function testItStoresXmlUsedAsFile(): void
    {
        $tmp_dir = vfsStream::setup();
        ForgeConfig::set('sys_data_dir', $tmp_dir->url());

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(101);

        $xml_saver = new TrackerXmlSaver();
        $xml_saver->storeUsedXmlForTrackersCreation($project, $root);
    }
}
