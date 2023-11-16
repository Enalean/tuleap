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
final class ArtifactTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    public function testAddDependenciesSimple(): void
    {
        $a = $this->getMockBuilder(\Artifact::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['insertDependency', 'validArtifact', 'existDependency'])
            ->getMock();

        $a->data_array = ['artifact_id' => 147];
        $a->method('insertDependency')->willReturn(true);
        $a->method('validArtifact')->willReturn(true);
        $a->method('existDependency')->willReturn(false);
        $changes = null;
        self::assertTrue($a->addDependencies("171", $changes, false), "It should be possible to add a dependency like 171");
    }

    public function testAddWrongDependency(): void
    {
        $a = $this->getMockBuilder(\Artifact::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['insertDependency', 'validArtifact'])
            ->getMock();

        $a->data_array = ['artifact_id' => 147];
        $a->method('insertDependency')->willReturn(true);
        $a->method('validArtifact')->willReturn(false);
        //$a->setReturnValue('existDependency', false);
        $changes = null;
        $GLOBALS['Response']->expects(self::exactly(2))->method('addFeedback');
        self::assertFalse($a->addDependencies("99999", $changes, false), "It should be possible to add a dependency like 99999 because it is not a valid artifact");
    }

    public function testAddDependenciesDouble(): void
    {
        $a = $this->getMockBuilder(\Artifact::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['insertDependency', 'validArtifact', 'existDependency'])
            ->getMock();

        $a->data_array = ['artifact_id' => 147];
        $a->method('insertDependency')->willReturn(true);
        $a->method('validArtifact')->willReturn(true);
        $a->method('existDependency')->willReturnOnConsecutiveCalls(false, true);
        $changes = null;
        self::assertTrue($a->addDependencies("171, 171", $changes, false), "It should be possible to add two identical dependencies in the same time, without getting an exception");
    }

    public function testFormatFollowUp(): void
    {
        $art = $this->getMockBuilder(\Artifact::class)->disableOriginalConstructor()->onlyMethods([])->getMock();

        $txtContent  = 'testing the feature';
        $htmlContent = '&lt;pre&gt;   function processEvent($event, $params) {&lt;br /&gt;       foreach(parent::processEvent($event, $params) as $key =&amp;gt; $value) {&lt;br /&gt;           $params[$key] = $value;&lt;br /&gt;       }&lt;br /&gt;   }&lt;br /&gt;&lt;/pre&gt; ';
        //the output will be delivered in a mail
        self::assertEquals('   function processEvent($event, $params) {       foreach(parent::processEvent($event, $params) as $key => $value) {           $params[$key] = $value;       }   } ', $art->formatFollowUp(102, 1, $htmlContent, 2));
        self::assertEquals($txtContent, $art->formatFollowUp(102, 0, $txtContent, 2));

        //the output is destinated to be exported
        self::assertEquals('<pre>   function processEvent($event, $params) {<br />       foreach(parent::processEvent($event, $params) as $key =&gt; $value) {<br />           $params[$key] = $value;<br />       }<br />   }<br /></pre> ', $art->formatFollowUp(102, 1, $htmlContent, 1));
        self::assertEquals($txtContent, $art->formatFollowUp(102, 0, $txtContent, 1));

        //The output will be displayed on browser
        self::assertEquals('<pre>   function processEvent($event, $params) {<br />       foreach(parent::processEvent($event, $params) as $key =&gt; $value) {<br />           $params[$key] = $value;<br />       }<br />   }<br /></pre> ', $art->formatFollowUp(102, 1, $htmlContent, 0));
        self::assertEquals($txtContent, $art->formatFollowUp(102, 0, $txtContent, 0));
    }
}
