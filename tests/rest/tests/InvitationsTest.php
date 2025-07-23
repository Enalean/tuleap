<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace rest\tests;

use Test\Rest\TuleapConfig;
use Tuleap\REST\ForgeAccessSandbox;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class InvitationsTest extends \RestBase
{
    use ForgeAccessSandbox;

    private TuleapConfig $tuleap_config;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->tuleap_config = TuleapConfig::instance();
        $this->setForgeToAnonymous();
    }

    public function testOptions(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'invitations'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostAsAnonymous(): void
    {
        $invitation = json_encode(
            [
                'emails' => ['john@example.com'],
            ],
        );
        $response   = $this->getResponseWithoutAuth(
            $this->request_factory->createRequest('POST', 'invitations')
                ->withBody($this->stream_factory->createStream($invitation))
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testPostAsRegisteredUSerWhenFeatureIsDeactivated(): void
    {
        $this->tuleap_config->disableInviteBuddies();

        $invitation = json_encode(
            [
                'emails' => ['john@example.com'],
            ],
        );
        $response   = $this->getResponse(
            $this->request_factory->createRequest('POST', 'invitations')
                ->withBody($this->stream_factory->createStream($invitation))
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostHappyPath(): void
    {
        $this->tuleap_config->enableInviteBuddies();

        $invitation = json_encode(
            [
                'emails' => ['john@example.com'],
            ],
        );
        $response   = $this->getResponse(
            $this->request_factory->createRequest('POST', 'invitations')
                ->withBody($this->stream_factory->createStream($invitation))
        );

        self::assertEquals(201, $response->getStatusCode());
    }
}
