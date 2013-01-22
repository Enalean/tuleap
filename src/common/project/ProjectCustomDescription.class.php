<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * Value Object of a Custom Project Description Field
 */
class ProjectCustomDescription {

    const REQUIRED     = true;
    const NOT_REQUIRED = false;

    const TYPE_TEXT = 'text';
    const TYPE_LINE = 'line';

    private $id;
    private $name;
    private $description;
    private $is_required;
    private $type;
    private $rank;

    public function __construct($id, $name, $description, $is_required, $type, $rank) {
        $this->id          = $id;
        $this->name        = $name;
        $this->description = $description;
        $this->is_required = $is_required;
        $this->type        = $type;
        $this->rank        = $rank;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->getTranslation($this->name);
    }

    public function getDescription() {
        return $this->getTranslation($this->description);
    }

    public function isRequired() {
        return $this->is_required;
    }

    public function getType() {
        return $this->type;
    }

    public function getRank() {
        return $this->rank;
    }

    public function isText() {
        return $this->getType() == self::TYPE_TEXT;
    }

    public function getTranslation($text) {
        if(preg_match('/(.*):(.*)/', $text, $matches)) {
            if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                return $GLOBALS['Language']->getText($matches[1], $matches[2]);
            }
        }
        return $text;
    }
}
?>
