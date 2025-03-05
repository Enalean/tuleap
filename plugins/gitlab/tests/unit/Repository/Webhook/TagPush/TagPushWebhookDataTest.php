<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TagPushWebhookDataTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsTagNameFromPath(): void
    {
        $webhook_data = new TagPushWebhookData(
            'Tag Push Event',
            1254,
            'https://example.com',
            'refs/tags/v1.0.1',
            'before',
            'after'
        );

        self::assertSame('v1.0.1', $webhook_data->getTagName());
    }
}
