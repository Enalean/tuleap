<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class WebhookXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var WebhookXMLExporter
     */
    private $exporter;

    public function setUp(): void
    {
        parent::setUp();

        $this->webhook_factory = \Mockery::mock(WebhookFactory::class);
        $this->exporter  = new WebhookXMLExporter($this->webhook_factory);

        $this->tracker   = \Mockery::mock(\Tracker::class);
    }

    public function testItDoesNothingIfNoWebhookDefinedForTracker()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');
        $this->webhook_factory->shouldReceive('getWebhooksForTracker')->with($this->tracker)->once()->andReturn([]);

        $this->exporter->exportTrackerWebhooksInXML($xml, $this->tracker);

        $this->assertEquals(count($xml->children()), 0);
    }

    public function testItExportWebhooksDefinedForTracker()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');
        $this->webhook_factory->shouldReceive('getWebhooksForTracker')->with($this->tracker)->once()->andReturn([
            new Webhook(1, 1, 'https://example.com/01'),
            new Webhook(2, 1, 'https://example.com/02'),
        ]);

        $this->exporter->exportTrackerWebhooksInXML($xml, $this->tracker);

        $this->assertEquals(count($xml->children()), 1);
        $this->assertEquals((string) $xml->webhooks->webhook[0]['url'], 'https://example.com/01');
        $this->assertEquals((string) $xml->webhooks->webhook[1]['url'], 'https://example.com/02');
    }
}
