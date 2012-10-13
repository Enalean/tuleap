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

/**
 * A presenter of card linked to card.mustache
 */
interface Tracker_CardPresenter {
    
    /**
     * @return int
     */
    public function getId();

    /**
     * @var string
     */
    public function getTitle();
    
    /**
     * @var array
     */
    public function getFields();

    /**
     * @var string
     */
    public function getUrl();

    /**
     * @var string
     */
    public function getXRef();

    /**
     * @var string
     */
    public function getEditUrl();

    /**
     * @var string
     */
    public function getArtifactId();

    /**
     * @var int
     */
    public function getAncestorId();

    /**
     * @return Tracker_Artifact
     */
    public function getArtifact();

    /**
     * @var string
     */
    public function getEditLabel();

    /**
     * @var string
     */
    public function getCssClasses();

    /**
     * @var array of Tracker
     */
    public function allowedChildrenTypes();
}
?>
