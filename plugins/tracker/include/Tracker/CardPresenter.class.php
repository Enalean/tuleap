<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

/**
 * A presenter of card linked to card.mustache
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
interface Tracker_CardPresenter
{
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
     * @return Artifact
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
