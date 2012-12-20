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

class Tracker_FormElement_Field_CrossReferencesTest extends TuleapTestCase {

    public function itReturnsTheReferencesInSOAPFormat() {
        $id       = $tracker_id = $parent_id = $name = $label = $description = $use_it = $scope = $required = $notifications = $rank = 0;
        $factory  = mock('CrossReferenceFactory');
        $artifact = mock('Tracker_Artifact');
        $field    = partial_mock(
            'Tracker_FormElement_Field_CrossReferences',
            array('getCrossReferenceFactory'),
            array($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank)
        );
        $wiki_ref = array(
            'ref' => 'wiki #toto',
            'url' => 'http://example.com/le_link_to_teh_wiki'
        );
        $file_ref = array(
            'ref' => 'file #chapeau',
            'url' => 'http://example.com/files/chapeau'
        );
        $art_ref = array(
            'ref' => 'art #123',
            'url' => 'http://example.com/tracker/123'
        );
        $doc_ref = array(
            'ref' => 'doc #42',
            'url' => 'http://example.com/docman/42'
        );

        stub($field)->getCrossReferenceFactory($artifact)->returns($factory);
        stub($factory)->getCrossReferencesByDirection()->returns(
            array(
                'source' => array($wiki_ref, $file_ref),
                'target' => array($art_ref),
                'both'   => array($doc_ref),
            )
        );
        $soap = $field->getSOAPValue($artifact);
        $this->assertEqual($soap, array(
            $wiki_ref,
            $file_ref,
            $art_ref,
            $doc_ref
        ));
    }
}

?>
