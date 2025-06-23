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

namespace Tuleap\Cardwall;

use Cardwall_OnTop_Config;
use Cardwall_OnTop_ConfigFactory;
use CardwallConfigXmlExport;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use SimpleXMLElement;
use TrackerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\XML\ParseExceptionWithErrors;
use XML_ParseException;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardwallConfigXmlExportTest extends TestCase
{
    private Project $project;
    private Tracker $tracker1;
    private Tracker $tracker2;
    private Cardwall_OnTop_Config&MockObject $cardwall_config;
    private Cardwall_OnTop_Config&MockObject $cardwall_config2;
    private TrackerFactory&MockObject $tracker_factory;
    private XML_RNGValidator&MockObject $xml_validator;
    private CardwallConfigXmlExport $xml_exporter;
    private SimpleXMLElement $root;
    private Cardwall_OnTop_ConfigFactory&MockObject $config_factory;

    protected function setUp(): void
    {
        $this->project  = ProjectTestBuilder::aProject()->withId(140)->build();
        $this->tracker1 = TrackerTestBuilder::aTracker()->withId(214)->build();
        $this->tracker2 = TrackerTestBuilder::aTracker()->withId(614)->build();
        $this->root     = new SimpleXMLElement('<projects/>');

        $this->cardwall_config = $this->createMock(Cardwall_OnTop_Config::class);
        $this->cardwall_config->method('isEnabled')->willReturn(false);
        $this->cardwall_config2 = $this->createMock(Cardwall_OnTop_Config::class);
        $this->cardwall_config2->method('isEnabled')->willReturn(true);

        $this->tracker_factory = $this->createMock(TrackerFactory::class);
        $this->tracker_factory->method('getTrackersByGroupId')->with(140)->willReturn([214 => $this->tracker1, 614 => $this->tracker2]);

        $this->config_factory = $this->createMock(Cardwall_OnTop_ConfigFactory::class);
        $this->xml_validator  = $this->createMock(XML_RNGValidator::class);
        $this->xml_validator->method('validate');

        $this->xml_exporter = new CardwallConfigXmlExport(
            $this->project,
            $this->tracker_factory,
            $this->config_factory,
            $this->xml_validator
        );
    }

    public function testItReturnsTheGoodRootXmlWithTrackers(): void
    {
        $matcher = self::exactly(2);
        $this->config_factory->expects($matcher)->method('getOnTopConfig')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->tracker1, $parameters[0]);
                return $this->cardwall_config;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->tracker2, $parameters[0]);
                return $this->cardwall_config2;
            }
        });

        $this->cardwall_config2->method('getDashboardColumns')->willReturn([]);

        $this->xml_exporter->export($this->root);
        $attributes = $this->root->cardwall->trackers->tracker->attributes();
        self::assertCount(1, $this->root->cardwall->trackers->children());
        self::assertEquals('T614', (string) $attributes['id']);
    }

    public function testItReturnsTheGoodRootXmlWithoutTrackers(): void
    {
        $cardwall_config = $this->createMock(Cardwall_OnTop_Config::class);
        $cardwall_config->method('isEnabled')->willReturn(false);
        $cardwall_config2 = $this->createMock(Cardwall_OnTop_Config::class);
        $cardwall_config2->method('isEnabled')->willReturn(false);
        $config_factory2 = $this->createMock(Cardwall_OnTop_ConfigFactory::class);
        $matcher         = $this->exactly(2);

        $config_factory2->expects($matcher)->method('getOnTopConfig')->willReturnCallback(function (...$parameters) use ($matcher, $cardwall_config, $cardwall_config2) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->tracker1, $parameters[0]);
                return $cardwall_config;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->tracker2, $parameters[0]);
                return $cardwall_config2;
            }
        });

        $xml_exporter2 = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $config_factory2, $this->xml_validator);

        $xml_exporter2->export($this->root);
        self::assertCount(0, $this->root->cardwall->trackers->children());
    }

    public function testItThrowsAnExceptionIfXmlGeneratedIsNotValid(): void
    {
        $matcher = $this->exactly(2);
        $this->config_factory->expects($matcher)->method('getOnTopConfig')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->tracker1, $parameters[0]);
                return $this->cardwall_config;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->tracker2, $parameters[0]);
                return $this->cardwall_config2;
            }
        });

        $this->cardwall_config2->method('getDashboardColumns')->willReturn([]);

        $xml_validator = $this->createMock(XML_RNGValidator::class);
        $xml_validator->method('validate')->willThrowException(new ParseExceptionWithErrors('', [], []));

        $xml_exporter = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory, $xml_validator);
        self::expectException(XML_ParseException::class);
        $xml_exporter->export(new SimpleXMLElement('<empty/>'));
    }
}
