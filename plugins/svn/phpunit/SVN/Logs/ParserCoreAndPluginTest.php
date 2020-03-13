<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class ParserCoreAndPluginTest extends TestCase
{
    public function testItReturnsLogsFromCoreAndPlugin()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.5.log');
        $this->assertEquals(
            $log_cache->getProjects(),
            array(
                'scrum-08' => array(
                    'zorglub' => array(
                        'vaceletm' => array(
                            '20170321' => array(
                                'write' => 0,
                                'read'  => 1,
                            )
                        ),
                        'alice' => array(
                            '20161111' => array(
                                'write' => 1,
                                'read'  => 0,
                            )
                        )
                    )
                )
            )
        );
        $this->assertEquals(
            $log_cache->getCoreProjects(),
            [
                'scrum-08' => [
                    'vaceletm' => [
                        '20170321' => [
                            'write' => 0,
                            'read'  => 1,
                        ]
                    ],
                    'alice' => [
                        '20161111' => [
                            'write' => 1,
                            'read'  => 0,
                        ]
                    ]
                ]
            ]
        );
    }


    public function testItReturnsLastAccessTimeStampForUsers()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__ . '/_fixtures/svn.5.log');
        $this->assertEquals(
            $log_cache->getLastAccessTimestamps(),
            array(
                'vaceletm' => 1490094561,
                'alice'    => 1478863364,
            )
        );
    }
}
