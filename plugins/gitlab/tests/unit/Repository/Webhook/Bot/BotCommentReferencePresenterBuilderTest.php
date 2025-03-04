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

namespace Tuleap\Gitlab\Repository\Webhook\Bot;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class BotCommentReferencePresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private BotCommentReferencePresenterBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        \ForgeConfig::set('sys_default_domain', 'example.com');
        $this->builder = new BotCommentReferencePresenterBuilder();
    }

    public function testItReturnsPresenterWithOneReference(): void
    {
        $references = [new WebhookTuleapReference(123, null)];

        $presenters = $this->builder->build($references);
        self::assertCount(1, $presenters);
        self::assertEquals('TULEAP-' . 123, $presenters[0]->label);
        self::assertEquals('https://example.com/plugins/tracker/?aid=123', $presenters[0]->url);
    }

    public function testItReturnsPresenterWithMultipleReferences(): void
    {
        $references = [new WebhookTuleapReference(123, null), new WebhookTuleapReference(59, null)];

        $presenters = $this->builder->build($references);
        self::assertCount(2, $presenters);
        self::assertEquals('TULEAP-' . 123, $presenters[0]->label);
        self::assertEquals('https://example.com/plugins/tracker/?aid=123', $presenters[0]->url);
        self::assertEquals('TULEAP-' . 59, $presenters[1]->label);
        self::assertEquals('https://example.com/plugins/tracker/?aid=59', $presenters[1]->url);
    }
}
