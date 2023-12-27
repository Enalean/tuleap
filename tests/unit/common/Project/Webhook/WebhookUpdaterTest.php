<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Project\Webhook;

final class WebhookUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCreatesAWebhook(): void
    {
        $dao     = $this->createMock(\Tuleap\Project\Webhook\WebhookDao::class);
        $updater = new WebhookUpdater($dao);

        $dao->expects(self::once())->method('createWebhook')->willReturn(true);
        $updater->add('Webhook name', 'https://example.com');
    }

    public function testItUpdatesAWebhook(): void
    {
        $dao     = $this->createMock(\Tuleap\Project\Webhook\WebhookDao::class);
        $updater = new WebhookUpdater($dao);

        $dao->expects(self::once())->method('editWebhook')->willReturn(true);
        $updater->edit(1, 'Webhook name', 'https://example.com');
    }

    public function testItDeletesAWebhook(): void
    {
        $dao     = $this->createMock(\Tuleap\Project\Webhook\WebhookDao::class);
        $updater = new WebhookUpdater($dao);

        $dao->expects(self::once())->method('deleteWebhookById')->willReturn(true);
        $updater->delete(1);
    }

    public function testItChecksDataBeforeManipulatingIt(): void
    {
        $dao     = $this->createMock(\Tuleap\Project\Webhook\WebhookDao::class);
        $updater = new WebhookUpdater($dao);

        self::expectException(\Tuleap\Project\Webhook\WebhookMalformedDataException::class);
        $dao->expects(self::never())->method('createWebhook');
        $dao->expects(self::never())->method('editWebhook');

        $updater->add('Webhook name', 'Not an URL');
        $updater->edit(1, 'Webhook name', 'Not an URL');
    }

    public function testItThrowsAnExceptionWhenDataCanNotBeProperlyAccessed(): void
    {
        $dao = $this->createMock(\Tuleap\Project\Webhook\WebhookDao::class);
        $dao->method('createWebhook')->willReturn(false);
        $updater = new WebhookUpdater($dao);

        self::expectException(\Tuleap\Project\Webhook\WebhookDataAccessException::class);

        $updater->add('Webhook name', 'https://example.com');
    }
}
