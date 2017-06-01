<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../include/HudsonJobWidget.class.php');

Mock::generatePartial(
    'HudsonJobWidget',
    'HudsonJobWidgetTestVersion',
    array('getAvailableJobs', 'initContent')
);

require_once(dirname(__FILE__).'/../include/HudsonJob.class.php');
Mock::generate('HudsonJob');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class HudsonJobWidgetTest extends TuleapTestCase {

    function testNoJobAvailable() {
        $hjw = new HudsonJobWidgetTestVersion($this);
        $hjw->setReturnValue('getAvailableJobs', array());
        $this->assertFalse($hjw->isInstallAllowed());
        $this->assertFalse($hjw->getInstallNotAllowedMessage() == '');
    }

    function testJobsAvailable() {
        $hjw = new HudsonJobWidgetTestVersion($this);
        $job1 = new MockHudsonJob();
        $job2 = new MockHudsonJob();
        $job3 = new MockHudsonJob();

        $hjw->setReturnValue('getAvailableJobs', array($job1, $job2, $job3));
        $this->assertTrue($hjw->isInstallAllowed());
        $this->assertEqual($hjw->getInstallNotAllowedMessage(), '');
    }

}
