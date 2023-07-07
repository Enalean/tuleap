<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

final class ParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsALogCache(): void
    {
        $parser = new Parser();
        self::assertInstanceOf(LogCache::class, $parser->parse(__DIR__ . '/_fixtures/svn.1.log'));
    }

    public function testItReturnsLogCacheARepository(): void
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.1.log');
        self::assertEquals(
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 1,
                                'read'  => 1,
                            ],
                        ],
                    ],
                ],
            ],
            $log_cache->getProjects(),
        );
    }

    public function testItReturnsLogCacheWithTwoUsers(): void
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.2.log');
        self::assertEquals(
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 3,
                                'read'  => 3,
                            ],
                        ],
                        'alice'    => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ],
                        ],
                    ],
                ],
            ],
            $log_cache->getProjects(),
        );
    }

    public function testItReturnsLogCacheWithMultipleRepo(): void
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.3.log');
        self::assertEquals(
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 3,
                                'read'  => 3,
                            ],
                        ],
                        'alice'    => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ],
                        ],
                    ],
                    'pouet'   => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 1,
                                'read'  => 0,
                            ],
                        ],
                    ],
                ],
                'zataz'    => [
                    'zorglub' => [
                        'bob' => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ],
                        ],
                    ],
                ],
            ],
            $log_cache->getProjects(),
        );
    }

    public function testItReturnsLogCacheWithDifferentDates(): void
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.4.log');
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
                        'alice'    => [
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
    }

    public function testItReturnsLastAccessTimeStampForUsers(): void
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.3.log');
        self::assertEquals(
            [
                'vaceletm' => 1490105171,
                'alice'    => 1490105053,
                'bob'      => 1490105053,
            ],
            $log_cache->getLastAccessTimestamps(),
        );
    }

    public function testItParsesUserWithSpacesInName(): void
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn-6.log');
        self::assertEquals(
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vacelet manuel' => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ],
                        ],
                    ],
                ],
            ],
            $log_cache->getProjects(),
        );
    }
}
