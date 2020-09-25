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

class InvitationsTest extends \RestBase
{
    /**
     * @var TuleapConfig
     */
    private $tuleap_config;

    public function __construct()
    {
        parent::__construct();
        $this->tuleap_config = TuleapConfig::instance();
        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testOptions(): void
    {
        $response = $this->getResponse($this->client->options('invitations'));

        $this->assertEquals(['OPTIONS', 'POST'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostAsAnonymous(): void
    {
        $invitation = json_encode(
            [
                'emails' => ["john@example.com"],
            ],
        );
        $response = $this->getResponseWithoutAuth($this->client->post('invitations', null, $invitation));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testPostAsRegisteredUSerWhenFeatureIsDeactivated(): void
    {
        $this->tuleap_config->disableInviteBuddies();

        $invitation = json_encode(
            [
                'emails' => ["john@example.com"],
            ],
        );
        $response = $this->getResponse($this->client->post('invitations', null, $invitation));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostHappyPathFailsBecauseTestEnvironmentIsNotAbleToSendEmails(): void
    {
        $this->tuleap_config->enableInviteBuddies();

        $invitation = json_encode(
            [
                'emails' => ["john@example.com"],
            ],
        );
        $response = $this->getResponse($this->client->post('invitations', null, $invitation));

        $body = json_decode((string) $response->getBody());
        $this->assertEquals("An error occurred while trying to send invitation", $body->error->i18n_error_message);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
