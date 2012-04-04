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

require_once dirname(__FILE__).'/../../../include/Tracker/CrossSearch/SemanticValueFactory.class.php';
require_once dirname(__FILE__).'/../../builders/aMockArtifact.php';
require_once dirname(__FILE__).'/../../builders/aMockArtifactFactory.php';
require_once dirname(__FILE__).'/../../builders/aMockSemanticTitleFactory.php';
require_once dirname(__FILE__).'/../../builders/aMockSemanticStatusFactory.php';

class Tracker_CrossSearch_SemanticValueFactory_NoSemanticTitleTest extends TuleapTestCase {
    public function itReturnsAnEmptyTitle() {
        $artifact                = aMockArtifact()->build();
        $artifact_factory        = aMockArtifactFactory()->withArtifact($artifact)->build();
        $semantic_title_factory  = aMockSemanticTitleFactory()->withNoFieldForTracker($artifact->getTracker())->build();
        $semantic_status_factory = aMockSemanticStatusFactory()->build();
        
        $semantic_value_factory = new Tracker_CrossSearch_SemanticValueFactory($artifact_factory, $semantic_title_factory, $semantic_status_factory);
        
        $artifact_id  = $artifact->getId();
        $changeset_id = 'whatever';
        
        $this->assertEqual($semantic_value_factory->getTitle($artifact_id, $changeset_id), '');
    }
}

class Tracker_CrossSearch_SemanticValueFactory_NoSemanticStatusTest extends TuleapTestCase {
    public function itReturnsAnEmptyStatus() {
        $artifact                = aMockArtifact()->build();
        $artifact_factory        = aMockArtifactFactory()->withArtifact($artifact)->build();
        $semantic_title_factory  = aMockSemanticTitleFactory()->build();
        $semantic_status_factory = aMockSemanticStatusFactory()->withNoFieldForTracker($artifact->getTracker())->build();
        
        $semantic_value_factory = new Tracker_CrossSearch_SemanticValueFactory($artifact_factory, $semantic_title_factory, $semantic_status_factory);
        
        $artifact_id  = $artifact->getId();
        $changeset_id = 'whatever';
        
        $this->assertEqual($semantic_value_factory->getStatus($artifact_id, $changeset_id), '');
    }
}

?>
