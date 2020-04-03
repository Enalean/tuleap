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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ArtifactTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    public function testAddDependenciesSimple(): void
    {
        $a = \Mockery::mock(\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $a->data_array = ['artifact_id' => 147];
        $a->shouldReceive('insertDependency')->andReturns(true);
        $a->shouldReceive('validArtifact')->andReturns(true);
        $a->shouldReceive('existDependency')->andReturns(false);
        $changes = null;
        $this->assertTrue($a->addDependencies("171", $changes, false), "It should be possible to add a dependency like 171");
    }

    public function testAddWrongDependency(): void
    {
        $a = \Mockery::mock(\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $a->data_array = ['artifact_id' => 147];
        $a->shouldReceive('insertDependency')->andReturns(true);
        $a->shouldReceive('validArtifact')->andReturns(false);
        //$a->setReturnValue('existDependency', false);
        $changes = null;
        $GLOBALS['Response']->shouldReceive('addFeedback')->times(2);
        $this->assertFalse($a->addDependencies("99999", $changes, false), "It should be possible to add a dependency like 99999 because it is not a valid artifact");
    }

    public function testAddDependenciesDouble(): void
    {
        $a = \Mockery::mock(\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $a->data_array = ['artifact_id' => 147];
        $a->shouldReceive('insertDependency')->andReturns(true);
        $a->shouldReceive('validArtifact')->andReturns(true);
        $a->shouldReceive('existDependency')->times(2)->andReturns(false, true);
        $changes = null;
        $this->assertTrue($a->addDependencies("171, 171", $changes, false), "It should be possible to add two identical dependencies in the same time, without getting an exception");
    }

    public function testFormatFollowUp(): void
    {
        $art = \Mockery::mock(\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $txtContent = 'testing the feature';
        $htmlContent = '&lt;pre&gt;   function processEvent($event, $params) {&lt;br /&gt;       foreach(parent::processEvent($event, $params) as $key =&amp;gt; $value) {&lt;br /&gt;           $params[$key] = $value;&lt;br /&gt;       }&lt;br /&gt;   }&lt;br /&gt;&lt;/pre&gt; ';
        //the output will be delivered in a mail
        $this->assertEquals('   function processEvent($event, $params) {       foreach(parent::processEvent($event, $params) as $key => $value) {           $params[$key] = $value;       }   } ', $art->formatFollowUp(102, 1, $htmlContent, 2));
        $this->assertEquals($txtContent, $art->formatFollowUp(102, 0, $txtContent, 2));

        //the output is destinated to be exported
        $this->assertEquals('<pre>   function processEvent($event, $params) {<br />       foreach(parent::processEvent($event, $params) as $key =&gt; $value) {<br />           $params[$key] = $value;<br />       }<br />   }<br /></pre> ', $art->formatFollowUp(102, 1, $htmlContent, 1));
        $this->assertEquals($txtContent, $art->formatFollowUp(102, 0, $txtContent, 1));

        //The output will be displayed on browser
        $this->assertEquals('<pre>   function processEvent($event, $params) {<br />       foreach(parent::processEvent($event, $params) as $key =&gt; $value) {<br />           $params[$key] = $value;<br />       }<br />   }<br /></pre> ', $art->formatFollowUp(102, 1, $htmlContent, 0));
        $this->assertEquals($txtContent, $art->formatFollowUp(102, 0, $txtContent, 0));
    }
}
