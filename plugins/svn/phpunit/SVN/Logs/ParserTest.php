<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class ParserTest extends TestCase
{

    public function testItReturnsALogCache()
    {
        $parser = new Parser();
        $this->assertInstanceOf(LogCache::class, $parser->parse(__DIR__ . '/_fixtures/svn.1.log'));
    }

    public function testItReturnsLogCacheARepository()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.1.log');
        $this->assertEquals(
            $log_cache->getProjects(),
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 1,
                                'read'  => 1,
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testItReturnsLogCacheWithTwoUsers()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.2.log');
        $this->assertEquals(
            $log_cache->getProjects(),
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 3,
                                'read'  => 3,
                            ]
                        ],
                        'alice'    => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testItReturnsLogCacheWithMultipleRepo()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.3.log');
        $this->assertEquals(
            $log_cache->getProjects(),
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 3,
                                'read'  => 3,
                            ]
                        ],
                        'alice'    => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ]
                        ]
                    ],
                    'pouet'   => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 1,
                                'read'  => 0,
                            ]
                        ]
                    ]
                ],
                'zataz'    => [
                    'zorglub' => [
                        'bob' => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testItReturnsLogCacheWithDifferentDates()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.4.log');
        $this->assertEquals(
            $log_cache->getProjects(),
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vaceletm' => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ]
                        ],
                        'alice'    => [
                            '20161111' => [
                                'write' => 1,
                                'read'  => 0,
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testItReturnsLastAccessTimeStampForUsers()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.3.log');
        $this->assertEquals(
            $log_cache->getLastAccessTimestamps(),
            [
                'vaceletm' => 1490105171,
                'alice'    => 1490105053,
                'bob'      => 1490105053,
            ]
        );
    }

    public function testItParsesUserWithSpacesInName()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn-6.log');
        $this->assertEquals(
            $log_cache->getProjects(),
            [
                'scrum-08' => [
                    'zorglub' => [
                        'vacelet manuel' => [
                            '20170321' => [
                                'write' => 0,
                                'read'  => 1,
                            ]
                        ]
                    ]
                ]
            ]
        );
    }
}
