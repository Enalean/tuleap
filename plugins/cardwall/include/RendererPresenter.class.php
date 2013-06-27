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
 * The content of the renderer
 */
class Cardwall_RendererPresenter extends Cardwall_BoardPresenter {

    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    public $field;

    /**
     * @var bool
     */
    public $has_columns;

    /**
     * @var string
     */
    public $warn_please_choose;

    /**
     * @var string
     */
    public $warn_no_values;

    public $is_display_avatar_selected = "";

    /**
     * @param Cardwall_Board                      $board              The board
     * @param Cardwall_QrCode                     $qrcode             QrCode to display. false if no qrcode (thus no typehinting)
     * @param string                              $redirect_parameter the redirect paramter to add to various url
     * @param Tracker_FormElement_Field_Selectbox $field              field used for columns. false if no qrcode (thus no typehinting)
     * @param Cardwall_Form                       $field              form to choose the column. false if no form (in widget) (thus no typehinting)
     */
    public function __construct(Cardwall_Board $board, $qrcode, $redirect_parameter, $field, $form) {
        parent::__construct($board, $qrcode, $redirect_parameter);
        $hp                        = Codendi_HTMLPurifier::instance();
        $this->nifty               = Toggler::getClassname('cardwall_board-nifty') == 'toggler' ? 'nifty' : false;
        $this->swimline_title      = '';
        $this->has_swimline_header = false;
        $this->field               = $field ? $field : false;
        $this->form                = $form  ? $form  : false;
        $this->has_columns         = count($this->board->columns) > 0;
        $this->warn_please_choose  = $GLOBALS['Language']->getText('plugin_cardwall', 'warn_please_choose');
        $field_label               = $field ? $hp->purify($this->field->getLabel()) : '###';
        $this->warn_no_values      = $GLOBALS['Language']->getText('plugin_cardwall', 'warn_no_values', $field_label);
    }
}
?>
