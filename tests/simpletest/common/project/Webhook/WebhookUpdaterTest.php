<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Webhook;

class WebhookUpdaterTest extends \TuleapTestCase
{
    public function itCreatesAWebhook()
    {
        $dao     = mock('Tuleap\\Project\\Webhook\\WebhookDao');
        stub($dao)->createWebhook()->returns(true);
        $updater = new WebhookUpdater($dao);

        $dao->expectOnce('createWebhook');
        $updater->add('Webhook name', 'https://example.com');
    }

    public function itChecksDataBeforeManipulatingIt()
    {
        $dao     = mock('Tuleap\\Project\\Webhook\\WebhookDao');
        $updater = new WebhookUpdater($dao);

        $this->expectException('Tuleap\\Project\\Webhook\\WebhookMalformedDataException');
        $dao->expectNever('createWebhook');

        $updater->add('Webhook name', 'Not an URL');
    }

    public function itThrowsAnExceptionWhenDataCanNotBeProperlyAccessed()
    {
        $dao     = mock('Tuleap\\Project\\Webhook\\WebhookDao');
        stub($dao)->createWebhook()->returns(false);
        $updater = new WebhookUpdater($dao);

        $this->expectException('Tuleap\\Project\\Webhook\\WebhookDataAccessException');

        $updater->add('Webhook name', 'https://example.com');
    }
}
