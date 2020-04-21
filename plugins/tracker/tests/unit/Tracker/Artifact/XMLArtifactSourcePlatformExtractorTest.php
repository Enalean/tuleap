<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Logger;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker\Artifact\XMLArtifactSourcePlatformExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Valid_HTTPURI;

require_once __DIR__ . '/../../bootstrap.php';

class XMLArtifactSourcePlatformExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLArtifactSourcePlatformExtractor
     */
    private $xml_source_platform_extractor;

    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    public function setUp(): void
    {
        $this->config = \Mockery::mock(ImportConfig::class);

        $this->logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);

        $this->xml_source_platform_extractor = new XMLArtifactSourcePlatformExtractor(new Valid_HTTPURI(), $this->logger);
    }

    public function testUpdateModeGetNullIfNoSourcePlatform()
    {
        $this->config->shouldReceive("isUpdate")->andReturn(true);

        $xml_field_mapping = file_get_contents(__DIR__ . "/_fixtures/testImportChangesetInArtifactWithoutSourcePlatformAttribute.xml");
        $xml_input = simplexml_load_string($xml_field_mapping);

        $this->logger->shouldReceive('warning')->with("No attribute source_platform in XML. New artifact created.");

        $source_platform = $this->xml_source_platform_extractor->getSourcePlatform($xml_input, $this->config, $this->logger);
        $this->assertEquals(null, $source_platform);
    }

    public function testUpdateModeGetNullIfWrongSourcePlatform()
    {
        $this->config->shouldReceive("isUpdate")->andReturn(true);

        $xml_field_mapping = file_get_contents(__DIR__ . "/_fixtures/testImportChangesetInArtifactWithWrongSourcePlatformAttribute.xml");
        $xml_input = simplexml_load_string($xml_field_mapping);

        $this->logger->shouldReceive('warning')->with("Source platform is not a valid URI. New artifact created.");

        $source_platform = $this->xml_source_platform_extractor->getSourcePlatform($xml_input, $this->config, $this->logger);
        $this->assertEquals(null, $source_platform);
    }

    public function testUpdateModeGetSourcePlatformIfSourcePlatformIsOk()
    {
        $this->config->shouldReceive("isUpdate")->andReturn(true);

        $xml_field_mapping = file_get_contents(__DIR__ . "/_fixtures/testImportChangesetInNewArtifact.xml");
        $xml_input = simplexml_load_string($xml_field_mapping);

        $source_platform = $this->xml_source_platform_extractor->getSourcePlatform($xml_input, $this->config, $this->logger);
        $this->assertEquals("https://web/", $source_platform);
    }

    public function testStandardModeGetNullIfNoSourcePlatform()
    {
        $this->config->shouldReceive("isUpdate")->andReturn(false);

        $xml_field_mapping = file_get_contents(__DIR__ . "/_fixtures/testImportChangesetInArtifactWithoutSourcePlatformAttribute.xml");
        $xml_input = simplexml_load_string($xml_field_mapping);

        $source_platform = $this->xml_source_platform_extractor->getSourcePlatform($xml_input, $this->config, $this->logger);
        $this->assertEquals(null, $source_platform);
    }

    public function testStandardModeGetSourcePlatformIfSourcePlatformIsOk()
    {
        $this->config->shouldReceive("isUpdate")->andReturn(false);

        $xml_field_mapping = file_get_contents(__DIR__ . "/_fixtures/testImportChangesetInNewArtifact.xml");
        $xml_input = simplexml_load_string($xml_field_mapping);

        $source_platform = $this->xml_source_platform_extractor->getSourcePlatform($xml_input, $this->config, $this->logger);
        $this->assertEquals("https://web/", $source_platform);
    }
}
