<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Webhook;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebhookXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private WebhookXMLExporter $exporter;
    private WebhookFactory&MockObject $webhook_factory;
    private Tracker $tracker;

    public function setUp(): void
    {
        parent::setUp();

        $this->webhook_factory = $this->createMock(WebhookFactory::class);
        $this->exporter        = new WebhookXMLExporter($this->webhook_factory);

        $this->tracker = TrackerTestBuilder::aTracker()->build();
    }

    public function testItDoesNothingIfNoWebhookDefinedForTracker()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');
        $this->webhook_factory->expects($this->once())->method('getWebhooksForTracker')->with($this->tracker)->willReturn([]);

        $this->exporter->exportTrackerWebhooksInXML($xml, $this->tracker);

        $this->assertEquals(count($xml->children()), 0);
    }

    public function testItExportWebhooksDefinedForTracker()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');
        $this->webhook_factory->expects($this->once())->method('getWebhooksForTracker')->with($this->tracker)->willReturn([
            new Webhook(1, 1, 'https://example.com/01'),
            new Webhook(2, 1, 'https://example.com/02'),
        ]);

        $this->exporter->exportTrackerWebhooksInXML($xml, $this->tracker);

        $this->assertEquals(count($xml->children()), 1);
        $this->assertEquals((string) $xml->webhooks->webhook[0]['url'], 'https://example.com/01');
        $this->assertEquals((string) $xml->webhooks->webhook[1]['url'], 'https://example.com/02');
    }
}
