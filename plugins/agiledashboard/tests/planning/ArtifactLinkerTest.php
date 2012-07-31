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

require_once dirname(__FILE__).'/../../include/Planning/ArtifactLinker.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/aMockArtifact.php';

class Planning_ArtifactLinkerTest extends TuleapTestCase {

    public function itDoesntLinkWhenItWasLinkedToAParent() {
        $user   = aUser()->build();
        $story_id = 5698;
        $story = aMockArtifact()->withId($story_id)->build();
        $task = aMockArtifact()->withId(2)->build();
        stub($task)->getAllAncestors($user)->returns(array($story));

        $request = aRequest()->with('link-artifact-id', "$story_id")->withUser($user)->build();
        
        $story->expectNever('linkArtifact');
        
        $artifact_factory = mock('Tracker_ArtifactFactory');
        $linker = new Planning_ArtifactLinker($artifact_factory);
        $linker->linkWithParents($request, $task);
    }
    
    public function itLinksWithAllHierarchyWhenItWasLinkedToAnAssociatedTracker() {
        $user   = aUser()->build();
        $epic_id = 2;
        $epic = aMockArtifact()->withId(2)->build();
        stub($epic)->getAllAncestors($user)->returns(array());

        $product = aMockArtifact()->withId(56)->build();
        
        $release_id = 7777;
        $release    = aMockArtifact()->withId($release_id)->build();
        stub($release)->getAllAncestors($user)->returns(array($product));
        
        $request = aRequest()->with('link-artifact-id', "$release_id")->withUser($user)->build();
        
        $epic->expectNever('linkArtifact');
        $release->expectNever('linkArtifact');
        $product->expectOnce('linkArtifact', array($epic_id, $user));
        
        $artifact_factory = mock('Tracker_ArtifactFactory');
        stub($artifact_factory)->getArtifactById($release_id)->returns($release);
        $linker = new Planning_ArtifactLinker($artifact_factory);
        $linker->linkWithParents($request, $epic);
    }

}

?>
