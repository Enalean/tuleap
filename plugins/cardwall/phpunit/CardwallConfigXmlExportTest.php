<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class CardwallConfigXmlExportTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker1;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker2;
    /**
     * @var Cardwall_OnTop_Config|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $cardwall_config;
    /**
     * @var Cardwall_OnTop_Config|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $cardwall_config2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|XML_RNGValidator
     */
    private $xml_validator;
    /** @var CardwallConfigXmlExport **/
    private $xml_exporter;

    /** @var SimpleXMLElement **/
    private $root;

    /** @var Cardwall_OnTop_ConfigFactory **/
    private $config_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project  = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(140);
        $this->tracker1 = Mockery::mock(Tracker::class);
        $this->tracker1->shouldReceive('getId')->andReturn(214);
        $this->tracker2 = Mockery::mock(Tracker::class);
        $this->tracker2->shouldReceive('getId')->andReturn(614);
        $this->root     = new SimpleXMLElement('<projects/>');

        $this->cardwall_config = Mockery::mock(\Cardwall_OnTop_Config::class);
        $this->cardwall_config->shouldReceive('isEnabled')->andReturn(false);
        $this->cardwall_config2 = Mockery::mock(\Cardwall_OnTop_Config::class);
        $this->cardwall_config2->shouldReceive('isEnabled')->andReturn(true);

        $this->tracker_factory = Mockery::mock(TrackerFactory::class);
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(140)->andReturn([214 => $this->tracker1, 614 => $this->tracker2]);

        $this->config_factory = \Mockery::spy(\Cardwall_OnTop_ConfigFactory::class);
        $this->xml_validator  = \Mockery::spy(\XML_RNGValidator::class);

        $this->xml_exporter = new CardwallConfigXmlExport(
            $this->project,
            $this->tracker_factory,
            $this->config_factory,
            $this->xml_validator
        );
    }

    public function testItReturnsTheGoodRootXmlWithTrackers(): void
    {
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker1)->once()->andReturn($this->cardwall_config);
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker2)->once()->andReturn($this->cardwall_config2);

        $this->cardwall_config2->shouldReceive('getDashboardColumns')->andReturn([]);

        $this->xml_exporter->export($this->root);
        $attributes = $this->root->cardwall->trackers->tracker->attributes();
        $this->assertCount(1, $this->root->cardwall->trackers->children());
        $this->assertEquals('T614', (string) $attributes['id']);
    }

    public function testItReturnsTheGoodRootXmlWithoutTrackers(): void
    {
        $cardwall_config = Mockery::spy(\Cardwall_OnTop_Config::class);
        $cardwall_config->shouldReceive('isEnabled')->andReturn(false);
        $cardwall_config2 = Mockery::spy(\Cardwall_OnTop_Config::class);
        $cardwall_config2->shouldReceive('isEnabled')->andReturn(false);
        $config_factory2 = \Mockery::spy(\Cardwall_OnTop_ConfigFactory::class);

        $config_factory2->shouldReceive('getOnTopConfig')->with($this->tracker1)->andReturns($cardwall_config);
        $config_factory2->shouldReceive('getOnTopConfig')->with($this->tracker2)->andReturns($cardwall_config2);

        $xml_exporter2 = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $config_factory2, $this->xml_validator);

        $xml_exporter2->export($this->root);
        $this->assertCount(0, $this->root->cardwall->trackers->children());
    }

    public function testItThrowsAnExceptionIfXmlGeneratedIsNotValid(): void
    {
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker1)->once()->andReturn($this->cardwall_config);
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker2)->once()->andReturn($this->cardwall_config2);

        $this->cardwall_config2->shouldReceive('getDashboardColumns')->andReturn([]);

        $xml_validator = Mockery::mock(XML_RNGValidator::class);
        $xml_validator->shouldReceive('validate')->andThrow(new XML_ParseException('', [], []));

        $xml_exporter = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory, $xml_validator);
        $this->expectException(XML_ParseException::class);
        $xml_exporter->export(new SimpleXMLElement('<empty/>'));
    }
}
