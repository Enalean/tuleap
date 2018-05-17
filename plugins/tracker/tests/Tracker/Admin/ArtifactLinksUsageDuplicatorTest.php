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
 */

namespace Tuleap\Tracker\Admin;

use TuleapTestCase;

require_once __DIR__.'/../../bootstrap.php';

class ArtifactLinksUsageDuplicatorTest extends TuleapTestCase
{

    /**
     * @var ArtifactLinksUsageDuplicator
     */
    private $duplicator;

    public function setUp()
    {
        parent::setUp();

        $this->dao        = mock('Tuleap\Tracker\Admin\ArtifactLinksUsageDao');
        $this->duplicator = new ArtifactLinksUsageDuplicator($this->dao);

        $this->template = aMockProject()->withId(101)->build();
        $this->project  = aMockProject()->withId(102)->build();
    }

    public function itActivatesTheArtifactLinkTypesIfTemplateAlreadyUseThem()
    {
        stub($this->dao)->isProjectUsingArtifactLinkTypes(101)->returns(true);

        expect($this->dao)->duplicate(101, 102)->once();

        $this->duplicator->duplicate($this->template, $this->project);
    }

    public function itActivatesTheArtifactLinkTypesIfTemplateDoesNotUseTrackerServiceAndNewProjectUseIt()
    {
        stub($this->dao)->isProjectUsingArtifactLinkTypes(101)->returns(false);
        stub($this->template)->usesService('plugin_tracker')->returns(false);
        stub($this->project)->usesService('plugin_tracker')->returns(true);

        expect($this->dao)->duplicate(101, 102)->once();

        $this->duplicator->duplicate($this->template, $this->project);
    }

    public function itDoesNotActivateTheArtifactLinkTypesIfTemplateDoesNotUseIt()
    {
        stub($this->dao)->isProjectUsingArtifactLinkTypes(101)->returns(false);
        stub($this->template)->usesService('plugin_tracker')->returns(true);
        stub($this->project)->usesService('plugin_tracker')->returns(true);

        expect($this->dao)->duplicate(101, 102)->never();

        $this->duplicator->duplicate($this->template, $this->project);
    }

    public function itDoesNotActivateTheArtifactLinkTypesIfTemplateAndNewProjectDoesNotUseTheService()
    {
        stub($this->dao)->isProjectUsingArtifactLinkTypes(101)->returns(false);
        stub($this->template)->usesService('plugin_tracker')->returns(false);
        stub($this->project)->usesService('plugin_tracker')->returns(false);

        expect($this->dao)->duplicate(101, 102)->never();

        $this->duplicator->duplicate($this->template, $this->project);
    }
}
