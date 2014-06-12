<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../../include/autoload.php';

class ResultFactoryTest extends TuleapTestCase {

    /** @var ElasticSearch_1_2_ResultFactory */
    private $result_factory;

    /** @var array */
    private $serach_data;

    public function setUp() {
        parent::setUp();
        $this->result_factory = new ElasticSearch_1_2_ResultFactory(mock('ProjectManager'));

        $this->serach_data = array(
            'took' => 2,
            'timed_out' => false,
            '_shards' => array(
                'total' => 1,
                'successful' => 1,
                'failed' => 0,
            ),
            'hits' => array(
                'total' => 2,
                'max_score' => 1.3838634,
                'hits' => array(
                    0 => array(
                        '_index' => 'tuleap',
                        '_type' => 'tracker',
                        '_id' => '3923',
                        '_score' => 1.3838634,
                        'fields' => array (
                            'group_id' => array (0 => 116, ),
                            'id' => array ( 0 => 3923, ),
                            'changeset_id' => array ( 0 => 3923, ),
                            'artifact_id' => array ( 0 => 22, ),
                        ),
                    ),
                    1 => array(
                        '_index' => 'tuleap',
                        '_type' => 'tracker',
                        '_id' => '3923',
                        '_score' => 1.3838634,
                        'fields' => array (
                            'group_id' => array (0 => 116, ),
                            'id' => array ( 0 => 3924, ),
                            'changeset_id' => array ( 0 => 3924, ),
                            'artifact_id' => array ( 0 => 23, ),
                        ),
                    ),
                ),
            ),
            'time' => 0.0044469833374023,
        );
    }

    public function itExtractChangesetIdsPerHits() {
        $expected_result = array(
            22 => 3923,
            23 => 3924
        );

        $this->assertEqual($this->result_factory->getChangesetIds($this->serach_data), $expected_result);
    }

    public function itDoesNothingIfThereAreNoHits() {
        $data= array(
            'took' => 2,
            'timed_out' => false,
            '_shards' => array(
                'total' => 1,
                'successful' => 1,
                'failed' => 0,
            ),
            'hits' => array(
                'total' => 0,
                'max_score' => 1.3838634,
            ),
            'time' => 0.0044469833374023,
        );

        $this->assertEqual($this->result_factory->getChangesetIds($data), array());
    }

    public function itExtractsTimeFromSearchData() {
        $this->assertEqual($this->result_factory->getQueryTime($this->serach_data), 0.0044469833374023);
    }

    public function itReturns0IfNoTimeInSearchData() {
        $this->assertEqual($this->result_factory->getQueryTime(array()), 0);
    }
}
