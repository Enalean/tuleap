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
 * A form presenter to let the use choose the columns on the cardwall
 */
class Cardwall_Form
{

    /**
     * @var int
     */
    public $report_id;

    /**
     * @var int
     */
    public $renderer_id;

    /**
     * @var int
     */
    public $printer_version;

    /**
     * @var bool
     */
    public $has_printer_version;

    /**
     * @var array
     */
    public $possible_columns;

    /**
     * @var bool
     */
    public $has_possible_columns;

    /**
     * @var bool
     */
    public $one_selected;

    /**
     * @var string
     */
    public $submit;

    public function __construct($report_id, $renderer_id, $printer_version, $field, array $selectboxes)
    {
        $this->report_id           = (int) $report_id;
        $this->renderer_id         = (int) $renderer_id;
        $this->printer_version     = (int) $printer_version;
        $this->has_printer_version = $printer_version === false;
        $this->possible_columns    = array();
        $this->one_selected        = false;

        $current_field_id = $field ? $field->getId() : false;
        foreach ($selectboxes as $form_element) {
            if ($form_element->userCanRead() && count($form_element->getAllValues())) {
                $selected = false;
                if ($form_element->getId() == $current_field_id) {
                    $selected           = true;
                    $this->one_selected = true;
                }
                $this->possible_columns[] = array(
                    'field'    => $form_element,
                    'selected' => $selected
                );
            }
        }
        $this->has_possible_columns = count($this->possible_columns) > 0;
        $this->submit               = $GLOBALS['Language']->getText('global', 'btn_submit');
        $this->please_choose_dashed = $GLOBALS['Language']->getText('global', 'please_choose_dashed');
    }
}
