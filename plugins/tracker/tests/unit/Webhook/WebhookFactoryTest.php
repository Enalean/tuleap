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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebhookFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var WebhookFactory
     */
    private $factory;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->factory = new WebhookFactory($this->createMock(WebhookDao::class));
    }

    public function testItCreatesWebhookObjectsFromXMLContent(): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<webhooks>
    <webhook url="https://example.com/01"/>
    <webhook url="https://example.com/02"/>
</webhooks>');

        $webhooks = $this->factory->getWebhooksFromXML($xml);

        $this->assertEquals(count($webhooks), 2);
        $this->assertEquals($webhooks[0]->getUrl(), 'https://example.com/01');
        $this->assertEquals($webhooks[1]->getUrl(), 'https://example.com/02');
    }
}
