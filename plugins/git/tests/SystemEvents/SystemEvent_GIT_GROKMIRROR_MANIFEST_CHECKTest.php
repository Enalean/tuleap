<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class SystemEvent_GIT_GROKMIRROR_MANIFEST_CHECKTest extends TuleapTestCase {

    /** @var SystemEvent_GIT_GROKMIRROR_MANIFEST_CHECK */
    private $event;

    public function setUp() {
        parent::setUp();

        $this->manifest_manager = mock('Git_Mirror_ManifestManager');
        $this->event = partial_mock('SystemEvent_GIT_GROKMIRROR_MANIFEST_CHECK', array('done', 'warning', 'error', 'getId'));

        $this->event->injectDependencies(
            $this->manifest_manager
        );
    }

    public function itChecksTheManifest() {
        expect($this->manifest_manager)->checkManifestFiles()->once();

        $this->event->process();
    }
}
