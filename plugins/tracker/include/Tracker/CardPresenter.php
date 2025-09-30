<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Tracker;

/**
 * A presenter of card linked to card.mustache
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
interface Tracker_CardPresenter
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string|null
     */
    public function getTitle();

    /**
     * @return array
     */
    public function getFields();

    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return string
     */
    public function getXRef();

    /**
     * @return string
     */
    public function getEditUrl();

    /**
     * @return int
     */
    public function getArtifactId();

    /**
     * @return int
     */
    public function getAncestorId();

    /**
     * @return Artifact
     */
    public function getArtifact();

    /**
     * @return string
     */
    public function getEditLabel();

    /**
     * @return string
     */
    public function getCssClasses();

    /**
     * @return Tracker[]
     */
    public function allowedChildrenTypes();

    /**
     * The accent color of the card to distinguish it among others.
     *
     * If no color, should returns empty string.
     *
     * @return string css compatible color
     */
    public function getAccentColor();

    /**
     * True when the accent color of the card is a css compatible color
     * False when it's a TLP color name
     * @return bool
     */
    public function hasLegacyAccentColor();

    /**
     * The value of the list field defined by the Card semantic
     *
     * @return string TLP color name
     */
    public function getBackgroundColorName();
}
