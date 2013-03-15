<?php

/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Tracker_Artifact_View_Child {

    /** @var Tracker_Artifact */
    private $child;
    
    /** @var Tracker_Semantic_Status */
    private $semantics;

    private $output_fields = array(
        'xref',
        'title',
        'id',
        'url',
        'status'
    );

    public function __construct(Tracker_Artifact $child, Tracker_Semantic_Status $semantics) {
        $this->child = $child;
        $this->semantics = $semantics;
    }

    public function toArray() {
        $array = array();

        $reflect = new ReflectionClass($this);
        $class_methods = $reflect->getMethods();

        foreach ($class_methods as $method) {
            $method_name = $method->name;
            $property = strtolower(substr($method_name, 3));
            if (in_array($property, $this->output_fields) && method_exists($this, $method_name)) {
                $array[$property] = $this->$method_name();
            }
        }

        return $array;
    }

    private function getXref() {
        return $this->child->getXRef();
    }

    private function getTitle() {
        return $this->child->getTitle();
    }

    private function getId() {
        return $this->child->getId();
    }

    private function getUrl() {
        $base_url = get_server_url();
        return $base_url.$this->child->getUri();
    }

    private function getStatus() {
        if (! $this->child->getStatus()) {
            $status = null;
        } else {
            $status = ( in_array($this->child->getStatus(), $this->semantics->getOpenLabels()) ) ? 1 : 0;
        }

        return $status;
    }

}
?>
