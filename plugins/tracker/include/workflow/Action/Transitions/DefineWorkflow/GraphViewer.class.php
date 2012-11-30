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

    /** @var array */
    private $graph_attributes = array(
        'spline' => 'ortho',
    );

    /** @var array */
    private $common_attributes = array(
        'fontname'  => 'arial',
        'fontsize'  => 10,
        'color'     => 'grey',
    );

    /** @var array */
    private $edge_attributes = array(
        'fontcolor' => '#0676B9',
    );

    /** @var array */
    private $nodes_attributes = array(
        'fillcolor' => 'grey96',
        'fontcolor' => 'grey27',
        'style'     => 'filled',
        'shape'     => 'box',
    );

    /** @var array */
    private $nil_attributes = array(
        'shape'     => 'point',
        'fillcolor' => 'grey',
    );

    public function __construct(Tracker $tracker, array $transitions) {
        $this->tracker     = $tracker;
        $this->transitions = $transitions;
        $this->inheritAttributes();
    }

    private function inheritAttributes() {
        $this->edge_attributes  = array_merge($this->common_attributes, $this->edge_attributes);
        $this->nodes_attributes = array_merge($this->common_attributes, $this->nodes_attributes);
        $this->nil_attributes   = array_merge($this->nodes_attributes, $this->nil_attributes);
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
        $gv->setAttributes($this->graph_attributes);
        foreach ($this->transitions as $transition) {
            $from   = $transition->getFieldValueFrom();
            $to     = $transition->getFieldValueTo();
            $from_node = $from ? $from->getLabel() : '__nil__';
            $to_node   = $to->getLabel();
            $attr = $from ? $this->nodes_attributes : $this->nil_attributes;
            $gv->addNode($from_node, $attr);
            $gv->addNode($to_node, $this->nodes_attributes);
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
                    $this->edge_attributes,
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
