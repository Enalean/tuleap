<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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


require_once __DIR__ .'/../include/autoload.php';
require_once __DIR__ .'/../../tracker/tests/bootstrap.php';
require_once __DIR__ .'/../../docman/tests/bootstrap.php';

class FullTextSearchTrackerActions_DefineMappingTest extends TuleapTestCase {

    private $client;
    private $tracker_data_factory;
    private $tracker;
    private $artifact;
    private $actions;
    private $logger;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->client               = \Mockery::spy(\FullTextSearch_IIndexDocuments::class);
        $this->tracker_data_factory = \Mockery::spy(\ElasticSearch_1_2_RequestTrackerDataFactory::class);
        $this->logger               = \Mockery::spy(\Logger::class);
        $this->tracker              = aTracker()->withId(455)->build();
        $this->artifact             = anArtifact()->withTracker($this->tracker)->build();
        $this->actions              = new FullTextSearchTrackerActions($this->client, $this->tracker_data_factory, $this->logger);

        stub($this->tracker_data_factory)->getFormattedArtifact($this->artifact)->returns(array('formatted artifact'));
    }

    public function itSetsMappingOnNewFollowUp() {
        stub($this->tracker_data_factory)->getTrackerMapping($this->tracker)->returns(array('dat result'));

        expect($this->client)->setMapping('455', array('dat result'))->once();

        $this->actions->indexArtifactUpdate($this->artifact);
    }

    public function itLogsMappingUpdates() {
        stub($this->tracker_data_factory)->getTrackerMapping($this->tracker)->returns(array('dat result'));

        expect($this->logger)->debug()->count(2);
        expect($this->logger)->debug('[Tracker] Elasticsearch set mapping for tracker #455');

        $this->actions->indexArtifactUpdate($this->artifact);
    }

    public function itDoesntSetMappingIfAlreadyExists() {
        stub($this->client)->getMapping('455')->returns(array('with stuff'));

        expect($this->client)->setMapping()->never();

        $this->actions->indexArtifactUpdate($this->artifact);
    }
}

class FullTextSearchTrackerActions_PushArtifactBaseTest extends TuleapTestCase {

    private $client;
    private $tracker_data_factory;
    private $tracker;
    private $artifact;
    private $actions;
    private $logger;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->client               = \Mockery::spy(\FullTextSearch_IIndexDocuments::class);
        $this->tracker_data_factory = \Mockery::spy(\ElasticSearch_1_2_RequestTrackerDataFactory::class);
        $this->logger               = \Mockery::spy(\Logger::class);
        $this->tracker              = aTracker()->withId(455)->build();
        $this->artifact             = anArtifact()->withId(44)->withTracker($this->tracker)->build();
        $this->actions              = new FullTextSearchTrackerActions($this->client, $this->tracker_data_factory, $this->logger);
    }

    public function itPushTheArtifactDocument() {
        stub($this->client)->getMapping('455')->returns(array('with stuff'));
        stub($this->tracker_data_factory)->getFormattedArtifact($this->artifact)->returns(array('formatted artifact'));

        expect($this->client)->index(455, 44, array('formatted artifact'))->once();

        $this->actions->indexArtifactUpdate($this->artifact);
    }

    public function itLogsTheArtifactUpdate() {
        stub($this->client)->getMapping('455')->returns(array('with stuff'));
        stub($this->tracker_data_factory)->getFormattedArtifact($this->artifact)->returns(array('formatted artifact'));

        expect($this->logger)->debug('[Tracker] Elasticsearch index artifact #44 in tracker #455')->once();

        $this->actions->indexArtifactUpdate($this->artifact);
    }
}
