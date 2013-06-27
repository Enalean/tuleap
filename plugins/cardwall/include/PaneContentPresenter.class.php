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
 * A board to display in agiledashboard
 */
class Cardwall_PaneContentPresenter extends Cardwall_BoardPresenter {

    /**
     * @var string
     */
    public $configure_url;

    /**
    * @var string
    */
    public $switch_display_username_url;

    /**
    * @var boolean
    */
    public $is_display_avatar_selected;

    /** @var string */
    public $search_cardwall_placeholder;

    /** @var int */
    public $planning_id;

    /**
     * @param Cardwall_Board  $board              The board
     * @param Cardwall_QrCode $qrcode             QrCode to display. false if no qrcode (thus no typehinting)
     * @param string          $redirect_parameter the redirect paramter to add to various url
     * @param string          $swimline_title     The title to display on top of swimline headers
     * @param Planning        $planning           The concerned planning
     */
    public function __construct(Cardwall_Board $board, $qrcode, $redirect_parameter, $swimline_title, $configure_url, $switch_display_username_url, $is_display_avatar_selected, Planning $planning) {
        parent::__construct($board, $qrcode, $redirect_parameter);
        $this->nifty                        = '';
        $this->swimline_title               = $swimline_title;
        $this->has_swimline_header          = true;
        $this->configure_url                = $configure_url;
        $this->configure_label              = $GLOBALS['Language']->getText('plugin_cardwall', 'configure_cardwall_label');
        $this->switch_display_username_url  = $switch_display_username_url;
        $this->is_display_avatar_selected   = $is_display_avatar_selected;
        $this->display_avatar_label         = $GLOBALS['Language']->getText('plugin_cardwall', 'display_avatar_label');
        $this->display_avatar_title         = $GLOBALS['Language']->getText('plugin_cardwall', 'display_avatar_title');
        $this->search_cardwall_placeholder  = $GLOBALS['Language']->getText('plugin_cardwall', 'search_cardwall_placeholder');
        $this->planning_id                  = $planning->getId();
    }

    public function canConfigure() {
        return $this->configure_url;
    }

    public function isDisplayAvatarSelected() {
        return $this->is_display_avatar_selected;
    }

    public function isUserLoggedIn() {
        return $this->switch_display_username_url;
    }
}
?>
