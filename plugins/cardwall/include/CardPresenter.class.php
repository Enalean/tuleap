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

use Tuleap\Cardwall\AccentColor\AccentColor;
use Tuleap\Cardwall\BackgroundColor\BackgroundColor;
use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Cardwall_CardPresenter implements Tracker_CardPresenter
{
    /**
     * @var Artifact
     */
    private $artifact;

    /**
     * @var Artifact
     */
    private $parent;

    /**
     * @var Cardwall_CardFields
     */
    private $card_fields;

    /** @var string */
    private $accent_color;

    /** @var Tracker[] */
    private $allowed_children;

    private $swimline_id;

    /** @var string */
    public $details;

    /** @var Cardwall_UserPreferences_UserPreferencesDisplayUser */
    private $display_preferences;

    /** @var BackgroundColor */
    private $background_color;

    /**
     * @var bool
     */
    public $user_has_accessibility_mode;

    public function __construct(
        PFUser $user,
        Artifact $artifact,
        Cardwall_CardFields $card_fields,
        AccentColor $accent_color,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $display_preferences,
        $swimline_id,
        array $allowed_children,
        BackgroundColor $background_color,
        ?Artifact $parent = null
    ) {
        $this->artifact                    = $artifact;
        $this->parent                      = $parent;
        $this->details                     = dgettext('tuleap-cardwall', 'details');
        $this->card_fields                 = $card_fields;
        $this->accent_color                = $accent_color;
        $this->display_preferences         = $display_preferences;
        $this->allowed_children            = $allowed_children;
        $this->swimline_id                 = $swimline_id;
        $this->background_color            = $background_color;
        $this->user_has_accessibility_mode = (bool) $user->getPreference(PFUser::ACCESSIBILITY_MODE);
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getId()
    {
        return $this->artifact->getId();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getTitle()
    {
        return $this->artifact->getTitle();
    }

    public function getFields()
    {
        $diplayed_fields_presenter = [];
        $displayed_fields = $this->card_fields->getFields($this->getArtifact());

        foreach ($displayed_fields as $displayed_field) {
            $diplayed_fields_presenter[] = new Cardwall_CardFieldPresenter($displayed_field, $this->artifact, $this->display_preferences);
        }
        return $diplayed_fields_presenter;
    }

    public function hasFields()
    {
        return count($this->getFields()) > 0;
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getUrl()
    {
        return $this->artifact->getUri();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getXRef()
    {
        return $this->artifact->getXRef();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getEditUrl()
    {
        return $this->getUrl();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getArtifactId()
    {
        return $this->artifact->getId();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    public function getAncestorId()
    {
        return $this->parent ? $this->parent->getId() : 0;
    }

    public function getSwimlineId()
    {
        return $this->swimline_id;
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getEditLabel()
    {
        return dgettext('tuleap-agiledashboard', 'Edit');
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getCssClasses()
    {
        $classes = '';
        $classes .= (! $this->hasLegacyAccentColor()) ? ' card-accent-' . $this->getAccentColor() : '';
        $classes .= ($this->getBackgroundColorName()) ? ' card-style-' . $this->getBackgroundColorName() : '';

        return $classes;
    }

    /**
     * @see Tracker_CardPresenter::getAccentColor()
     */
    public function getAccentColor()
    {
        return $this->accent_color->getColor();
    }

    /**
     * @see Tracker_CardPresenter::hasLegacyAccentColor()
     */
    public function hasLegacyAccentColor()
    {
        return $this->accent_color->isLegacyColor();
    }

    /**
     * @see Tracker_CardPresenter
     *
     * @return Tracker[]
     */
    public function allowedChildrenTypes()
    {
        return $this->allowed_children;
    }

    /**
     * @see Tracker_CardPresenter
     *
     * @return string TLP color name
     */
    public function getBackgroundColorName()
    {
        return $this->background_color->getBackgroundColorName();
    }
}
