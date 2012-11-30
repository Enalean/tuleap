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

require_once 'Image/GraphViz.php';

/**
 * Render the representation of a transition graph with GraphViz
 */
class Workflow_Action_Transitions_DefineWorkflow_GraphViewer {

    /** @var Tracker */
    private $tracker;

    /** @var array */
    private $transitions;

    public function __construct(Tracker $tracker, array $transitions) {
        $this->tracker     = $tracker;
        $this->transitions = $transitions;
    }

    /**
     * @return string svg representation of workflow transition that can be embedded in a html document
     */
    public function fetchEmbeddableSvg() {
        $svg = $this->fetchSvg();
        return $this->pruneXMLDeclaration($svg);
    }

    /** @return string svg */
    private function fetchSvg() {
        $gv = new Image_GraphViz();
        $gv->setAttributes(
            array(
                'spline' => 'ortho',
            )
        );
        $common_attributes = array(
            'fontname'  => 'arial',
            'fontsize'  => 10,
            'color'     => 'grey',
        );
        $edge_attributes = array_merge(
            $common_attributes,
            array(
                'fontcolor' => '#0676B9',
            )
        );
        $nodes_attributes = array_merge(
            $common_attributes,
            array(
                'fillcolor' => 'grey96',
                'fontcolor' => 'grey27',
                'style'     => 'filled',
                'shape'     => 'box',
            )
        );
        $nil_attributes = array_merge(
            $nodes_attributes,
            array(
                'shape'     => 'point',
                'fillcolor' => 'grey',
            )
        );
        foreach ($this->transitions as $transition) {
            $from   = $transition->getFieldValueFrom();
            $to     = $transition->getFieldValueTo();
            $from_node = $from ? $from->getLabel() : '__nil__';
            $to_node   = $to->getLabel();
            $attr = $from ? $nodes_attributes : $nil_attributes;
            $gv->addNode($from_node, $attr);
            $gv->addNode($to_node, $nodes_attributes);
            $url = TRACKER_BASE_URL.'/?'. http_build_query(
                array(
                    'tracker'         => (int)$this->tracker->getId(),
                    'func'            => Workflow::FUNC_ADMIN_TRANSITIONS,
                    'edit_transition' => $transition->getTransitionId()
                )
            );
            $gv->addEdge(
                array($from_node => $to_node),
                array_merge(
                    $edge_attributes,
                    array(
                        'label' => ($from ? $from_node : '') .' → '. $to_node,
                        'href'  => $url,
                    )
                )
            );
        }
        return $gv->fetch();
    }

    /** @return string xml without the declaration (<?xml version="..." ?>) */
    private function pruneXMLDeclaration($xml_string) {
        return substr($xml_string, strpos($xml_string, '<svg'));
    }
}
?>
