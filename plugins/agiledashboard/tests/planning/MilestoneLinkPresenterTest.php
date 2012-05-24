<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../include/Planning/MilestoneLinkPresenter.class.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../builders/aMilestone.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aMockArtifact.php';

class Planning_MilestoneLinkPresenterTest extends TuleapTestCase {
    
    public function setUp() {
        $this->artifact  = aMockArtifact()->withTitle('Foo')
                                          ->withUri('/bar/baz')
                                          ->withXRef('qux#123')->build();
        $this->milestone = aMilestone()->withArtifact($this->artifact)->build();
        $this->presenter = new Planning_MilestoneLinkPresenter($this->milestone);
    }
    
    public function itHasAnUri() {
        $this->assertEqual($this->presenter->getUri(), $this->artifact->getUri());
    }
    
    public function itHasAnXRef() {
        $this->assertEqual($this->presenter->getXRef(), $this->artifact->getXRef());
    }
    
    public function itHasATitle() {
        $this->assertEqual($this->presenter->getTitle(), $this->artifact->getTitle());
    }
}
?>
