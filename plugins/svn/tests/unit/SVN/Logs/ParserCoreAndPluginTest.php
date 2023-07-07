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

namespace Tuleap\SVN\Logs;

final class ParserCoreAndPluginTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsLogsFromCoreAndPlugin(): void
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.5.log');
        self::assertEquals(
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ],
                        ],
                        'alice' => [
                            '20161111' => [
                                'write' => 1,
                                'read'  => 0,
                            ],
                        ],
                    ],
                ],
            ],
            $log_cache->getProjects(),
        );
        self::assertEquals(
            [
                'scrum-08' => [
                    'vaceletm' => [
                        '20170321' => [
                            'write' => 0,
                            'read'  => 1,
                        ],
                    ],
                    'alice' => [
                        '20161111' => [
                            'write' => 1,
                            'read'  => 0,
                        ],
                    ],
                ],
            ],
            $log_cache->getCoreProjects(),
        );
    }

    public function testItReturnsLastAccessTimeStampForUsers(): void
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.5.log');
        self::assertEquals(
            [
                'vaceletm' => 1490094561,
                'alice'    => 1478863364,
            ],
            $log_cache->getLastAccessTimestamps(),
        );
    }
}
