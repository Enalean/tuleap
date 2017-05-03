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
 *
 */

namespace Tuleap\Svn\Logs;

require_once __DIR__ .'/../../bootstrap.php';

class ParserTest extends \TuleapTestCase
{

    public function itReturnsALogCache()
    {
        $parser = new Parser();
        $this->assertIsA($parser->parse(__DIR__.'/_fixtures/svn.1.log'), 'Tuleap\\Svn\\Logs\\LogCache');
    }

    public function itReturnsLogCacheARepository()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__.'/_fixtures/svn.1.log');
        $this->assertEqual(
            $log_cache->getProjects(),
            array(
                'scrum-08' => array(
                    'zorglub' => array(
                        'vaceletm' => array(
                            '20170321' => array(
                                'write' => 1,
                                'read'  => 1,
                            )
                        )
                    )
                )
            )
        );
    }

    public function itReturnsLogCacheWithTwoUsers()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__.'/_fixtures/svn.2.log');
        $this->assertEqual(
            $log_cache->getProjects(),
            array(
                'scrum-08' => array(
                    'zorglub' => array(
                        'vaceletm' => array(
                            '20170321' => array(
                                'write' => 3,
                                'read'  => 3,
                            )
                        ),
                        'alice' => array(
                            '20170321' => array(
                                'write' => 0,
                                'read'  => 1,
                            )
                        )
                    )
                )
            )
        );
    }

    public function itReturnsLogCacheWithMultipleRepo()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__.'/_fixtures/svn.3.log');
        $this->assertEqual(
            $log_cache->getProjects(),
            array(
                'scrum-08' => array(
                    'zorglub' => array(
                        'vaceletm' => array(
                            '20170321' => array(
                                'write' => 3,
                                'read'  => 3,
                            )
                        ),
                        'alice' => array(
                            '20170321' => array(
                                'write' => 0,
                                'read'  => 1,
                            )
                        )
                    ),
                    'pouet' => array(
                        'vaceletm' => array(
                            '20170321' => array(
                                'write' => 1,
                                'read'  => 0,
                            )
                        )
                    )
                ),
                'zataz' => array(
                    'zorglub' => array(
                        'bob' => array(
                            '20170321' => array(
                                'write' => 0,
                                'read'  => 1,
                            )
                        )
                    )
                )
            )
        );
    }

    public function itReturnsLogCacheWithDifferentDates()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__.'/_fixtures/svn.4.log');
        $this->assertEqual(
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
    }

    public function itReturnsLastAccessTimeStampForUsers()
    {
        $parser    = new Parser();
        $log_cache = $parser->parse(__DIR__.'/_fixtures/svn.3.log');
        $this->assertEqual(
            $log_cache->getLastAccessTimestamps(),
            array(
                'vaceletm' => 1490105171,
                'alice'    => 1490105053,
                'bob'      => 1490105053,
            )
        );
    }
}
