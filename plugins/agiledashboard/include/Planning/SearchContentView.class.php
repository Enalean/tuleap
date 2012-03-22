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

require_once 'common/mustache/MustacheRenderer.class.php';

class Planning_SearchContentView extends Tracker_CrossSearch_SearchContentView {

    protected function fetchTable() {
        $this->injectArtifactInChildren($this->tree_of_artifacts);
        $renderer = new MustacheRenderer(dirname(__FILE__) .'/../../templates');
        return $renderer->render('cards', $this, true);
    }

    private function injectArtifactInChildren(TreeNode $node) {
        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }

    public function visit(TreeNode $node) {
        $row = $node->getData();
        $artifact = $this->artifact_factory->getArtifactById($row['id']);
        if ($artifact) {
            $row['title'] = $artifact->getTitle();
            $row['link']  = $artifact->fetchDirectLinkToArtifact();
            $node->setData($row);
        }
        $this->injectArtifactInChildren($node);
    }

    public function getChildren() {
        return $this->tree_of_artifacts->getChildren();
    }
}
?>
