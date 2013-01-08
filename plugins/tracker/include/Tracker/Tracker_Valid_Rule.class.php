<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/valid/Rule.class.php');


/**
 * Check that given value is a valid artifact id
 */
class Tracker_Valid_Rule_ArtifactId extends Rule_Int {
    function isValid($val) {
        if (parent::isValid($val)) {
            $af = $this->getArtifactFactory();
            $artifact = $af->getArtifactById($val);
            return $artifact != null;
        } else {
            return false;
        }
    }
    public function getArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }
}

?>