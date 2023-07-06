<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\PreReceive;

use Psr\Log\NullLogger;
use Tuleap\Git\MarkTechnicalReference;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class PreReceiveHookDataWithoutTechnicalReferenceTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItRemovesTechnicalReference(): void
    {
        $event_dispatcher = EventDispatcherStub::withCallback(function (MarkTechnicalReference $event): MarkTechnicalReference {
            $event->markAsTechnical();
            return $event;
        });

        $input          = "a b refs/tlpr/42/head\n\r";
        $git_dir_path   = "/repo-git";
        $guest_dir_path = "/repo-git-guest";

        $result = PreReceiveHookData::fromRawStdinHook($input, $git_dir_path, $guest_dir_path, new NullLogger())->map(
            fn (PreReceiveHookData $hook_data): PreReceiveHookDataWithoutTechnicalReference => PreReceiveHookDataWithoutTechnicalReference::fromHookData($hook_data, $event_dispatcher, new NullLogger())
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals([], $result->value->updated_references);
    }

    public function testItDoesNotRemoveNonTechnicalReference(): void
    {
        $input                                     = "a b refs/heads/tuleap-pr\n\r";
        $git_dir_path                              = "/repo-git";
        $guest_dir_path                            = "/repo-git-guest";
        $updated_reference['refs/heads/tuleap-pr'] = new PreReceiveHookUpdatedReference('a', 'b');

        $result = PreReceiveHookData::fromRawStdinHook($input, $git_dir_path, $git_dir_path, new NullLogger())->map(
            fn (PreReceiveHookData $hook_data): PreReceiveHookDataWithoutTechnicalReference => PreReceiveHookDataWithoutTechnicalReference::fromHookData($hook_data, EventDispatcherStub::withIdentityCallback(), new NullLogger())
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals($updated_reference, $result->value->updated_references);
    }
}
