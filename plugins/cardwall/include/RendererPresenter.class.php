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

require_once 'BoardPresenter.class.php';

/**
 * The content of the renderer
 */
class Cardwall_RendererPresenter extends Cardwall_BoardPresenter {

    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    public $field;

    /**
     * @param array                               $swimlines Array of TreeNode
     * @param array                               $columns   Array of Cardwall_Column
     * @param Cardwall_MappingCollection          $mappings  Collection of Cardwall_Mapping
     * @param Cardwall_QrCode                     $qrcode    QrCode to display. false if no qrcode (thus no typehinting)
     * @param Tracker_FormElement_Field_Selectbox $field     field used for columns. false if no qrcode (thus no typehinting)
     * @param Cardwall_Form                       $field     form to choose the column. false if no form (in widget) (thus no typehinting)
     */
    public function __construct(array $swimlines, array $columns, Cardwall_MappingCollection $mappings, $qrcode, $field, $form) {
        parent::__construct($swimlines, $columns, $mappings, $qrcode);
        $this->nifty               = Toggler::getClassname('cardwall_board-nifty') == 'toggler' ? 'nifty' : false;
        $this->swimline_title      = '';
        $this->has_swimline_header = false;
        $this->field               = $field ? $field : false;
        $this->form                = $form ? $form : false;
    }

    /**
     * @return bool
     */
    public function has_columns() {
        return count($this->columns) > 0;
    }

    /**
     * @return string
     */
    public function warn_please_choose() {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'warn_please_choose');
    }

    /**
     * @return string
     */
    public function warn_no_values() {
        $hp = Codendi_HTMLPurifier::instance();
        return $GLOBALS['Language']->getText('plugin_cardwall', 'warn_no_values', $hp->purify($this->field->getLabel()));
    }
}
?>
