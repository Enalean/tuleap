<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

Mock::generatePartial('Artifact', 'ArtifactTestVersion', array('insertDependency', 'validArtifact', 'existDependency', 'addHistory', 'getReferenceManager', 'userCanEditFollowupComment'));

Mock::generate('ArtifactFieldFactory');

Mock::generate('ReferenceManager');

Mock::generate('ArtifactType');

Mock::generate('Codendi_HTMLPurifier');

require_once __DIR__ . '/../../../../src/www/include/utils.php';

class ArtifactTest extends TuleapTestCase
{


    public function testAddDependenciesSimple()
    {
        $a = new ArtifactTestVersion($this);
        $a->data_array = ['artifact_id' => 147];
        $a->setReturnValue('insertDependency', true);
        $a->setReturnValue('validArtifact', true);
        $a->setReturnValue('existDependency', false);
        $changes = null;
        $this->assertTrue($a->addDependencies("171", $changes, false), "It should be possible to add a dependency like 171");
    }

    public function testAddWrongDependency()
    {
        $a = new ArtifactTestVersion($this);
        $a->data_array = ['artifact_id' => 147];
        $a->setReturnValue('insertDependency', true);
        $a->setReturnValue('validArtifact', false);
        //$a->setReturnValue('existDependency', false);
        $changes = null;
        $this->assertFalse($a->addDependencies("99999", $changes, false), "It should be possible to add a dependency like 99999 because it is not a valid artifact");
        $GLOBALS['Response']->expectCallCount('addFeedback', 2);
    }

    public function testAddDependenciesDouble()
    {
        $a = new ArtifactTestVersion($this);
        $a->data_array = ['artifact_id' => 147];
        $a->setReturnValue('insertDependency', true);
        $a->setReturnValue('validArtifact', true);
        $a->setReturnValue('existDependency', false);
        $a->setReturnValueAt(0, 'existDependency', false);
        $a->setReturnValueAt(1, 'existDependency', true);
        $changes = null;
        $this->assertTrue($a->addDependencies("171, 171", $changes, false), "It should be possible to add two identical dependencies in the same time, without getting an exception");
    }

    public function testFormatFollowUp()
    {
        $art = new ArtifactTestVersion($this);

        $txtContent = 'testing the feature';
        $htmlContent = '&lt;pre&gt;   function processEvent($event, $params) {&lt;br /&gt;       foreach(parent::processEvent($event, $params) as $key =&amp;gt; $value) {&lt;br /&gt;           $params[$key] = $value;&lt;br /&gt;       }&lt;br /&gt;   }&lt;br /&gt;&lt;/pre&gt; ';
        //the output will be delivered in a mail
        $this->assertEqual('   function processEvent($event, $params) {       foreach(parent::processEvent($event, $params) as $key => $value) {           $params[$key] = $value;       }   } ', $art->formatFollowUp(102, 1, $htmlContent, 2));
        $this->assertEqual($txtContent, $art->formatFollowUp(102, 0, $txtContent, 2));

        //the output is destinated to be exported
        $this->assertEqual('<pre>   function processEvent($event, $params) {<br />       foreach(parent::processEvent($event, $params) as $key =&gt; $value) {<br />           $params[$key] = $value;<br />       }<br />   }<br /></pre> ', $art->formatFollowUp(102, 1, $htmlContent, 1));
        $this->assertEqual($txtContent, $art->formatFollowUp(102, 0, $txtContent, 1));

        //The output will be displayed on browser
        $this->assertEqual('<pre>   function processEvent($event, $params) {<br />       foreach(parent::processEvent($event, $params) as $key =&gt; $value) {<br />           $params[$key] = $value;<br />       }<br />   }<br /></pre> ', $art->formatFollowUp(102, 1, $htmlContent, 0));
        $this->assertEqual($txtContent, $art->formatFollowUp(102, 0, $txtContent, 0));
    }
}
