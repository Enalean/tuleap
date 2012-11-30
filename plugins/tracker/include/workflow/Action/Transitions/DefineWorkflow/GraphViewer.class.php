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

    const INITIAL_NODE_OF_THE_GRAPH = '__NIL__';

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
            $from_node = $this->addNode($gv, $transition->getFieldValueFrom());
            $to_node   = $this->addNode($gv, $transition->getFieldValueTo());
            $this->addEdge($gv, $transition->getId(), $from_node, $to_node);
        }
        return $gv->fetch();
    }

    /** @return string the node */
    private function addNode(Image_GraphViz $gv, Tracker_FormElement_Field_List_Value $value = null) {
        $node       = self::INITIAL_NODE_OF_THE_GRAPH;
        $attributes = $this->nil_attributes;
        if ($value) {
            $node       = $value->getLabel();
            $attributes = $this->nodes_attributes;
        }
        $gv->addNode($node, $attributes);
        return $node;
    }

    private function addEdge(Image_GraphViz $gv, $transition_id, $from_node, $to_node) {
        $nodes      = array($from_node => $to_node);
        $attributes = array_merge(
            $this->edge_attributes,
            array(
                'label' => $this->getEdgeLabel($from_node, $to_node),
                'href'  => $this->getUrl($transition_id),
            )
        );

        $gv->addEdge($nodes, $attributes);
    }

    /** @return string */
    private function getEdgeLabel($from_node, $to_node) {
        $label = $from_node;
        if ($from_node == self::INITIAL_NODE_OF_THE_GRAPH) {
            $label = '';
        }
        $label .= ' → '. $to_node;
        return $label;
    }

    /** @return string */
    private function getUrl($transition_id) {
        return TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker'         => (int)$this->tracker->getId(),
                'func'            => Workflow::FUNC_ADMIN_TRANSITIONS,
                'edit_transition' => $transition_id
            )
        );
    }

    /** @return string xml without the declaration (<?xml version="..." ?>) */
    private function pruneXMLDeclaration($xml_string) {
        return substr($xml_string, strpos($xml_string, '<svg'));
    }
}
?>
