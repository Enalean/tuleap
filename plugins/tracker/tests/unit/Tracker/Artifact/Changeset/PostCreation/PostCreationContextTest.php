<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tracker\Artifact\Changeset\PostCreation;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;

final class PostCreationContextTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public static function dataProviderSendNotifications(): array
    {
        return [
            'with notifications'    => [true],
            'without notifications' => [false],
        ];
    }

    /**
     * @dataProvider dataProviderSendNotifications
     */
    public function testItBuildsWithNoConfig(bool $send_notifications): void
    {
        $context = PostCreationContext::withNoConfig($send_notifications);
        self::assertSame($send_notifications, $context->shouldSendNotifications());
        self::assertInstanceOf(TrackerNoXMLImportLoggedConfig::class, $context->getImportConfig());
    }

    /**
     * @dataProvider dataProviderSendNotifications
     */
    public function testItBuildsWithConfig(bool $send_notifications): void
    {
        $user        = UserTestBuilder::buildWithDefaults();
        $import_time = new \DateTimeImmutable();
        $config      = new TrackerXmlImportConfig($user, $import_time, MoveImportConfig::buildForRegularImport());

        $context = PostCreationContext::withConfig($config, $send_notifications);
        self::assertSame($send_notifications, $context->shouldSendNotifications());
        self::assertSame($config, $context->getImportConfig());
    }
}
