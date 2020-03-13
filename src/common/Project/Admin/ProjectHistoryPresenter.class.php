<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class ProjectHistoryPresenter
{

    /** @var int */
    public $group_id;

    /** @var string */
    public $select_button;

    /** @var string */
    public $value;

    /** @var string */
    public $start_date;

    /** @var string */
    public $end_date;

    /** @var string */
    public $start_field_date;

    /** @var string */
    public $end_field_date;

    /** @var string */
    public $by;

    /** @var array */
    private $history_rows;

    /** @var array */
    public $titles;

    /** @var int */
    public $index;

    /** @var int */
    public $offset;

    /** @var int */
    public $limit;

    /** @var string */
    public $forward_sub_events;

    public function __construct(
        $group_id,
        $select_button,
        $value,
        $start_date,
        $end_date,
        $by,
        array $history_rows,
        array $titles,
        $index,
        $offset,
        $limit,
        $forward_sub_events
    ) {
        $this->group_id           = $group_id;
        $this->select_button      = $select_button;
        $this->value              = $value;
        $this->start_date         = $start_date;
        $this->end_date           = $end_date;
        $this->by                 = $by;
        $this->history_rows       = $history_rows;
        $this->titles             = $titles;
        $this->index              = $index;
        $this->offset             = $offset;
        $this->limit              = $limit;
        $this->forward_sub_events = $forward_sub_events;
    }

    public function start_field_date()
    {
        return $GLOBALS['HTML']->getBootstrapDatePicker(
            'history-search-start',
            'start',
            $this->start_date,
            array(),
            array(),
            false
        );
    }

    public function end_field_date()
    {
        return $GLOBALS['HTML']->getBootstrapDatePicker(
            'history-search-end',
            'end',
            $this->end_date,
            array(),
            array(),
            false
        );
    }

    public function title()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'g_change_history');
    }

    public function toggle_search()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'toggle_search');
    }

    public function toggler_class_name()
    {
        return Toggler::getClassname('history_search_title');
    }

    public function history_search_title()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'history_search_title');
    }

    public function table_head_event()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'event');
    }

    public function table_head_val()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'val');
    }

    public function table_head_from()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'from');
    }

    public function table_head_to()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'to');
    }

    public function table_head_by()
    {
        return $GLOBALS['Language']->getText('global', 'by');
    }

    public function choose_event_label()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'choose_event');
    }

    public function has_history_rows()
    {
        return $this->history_rows['numrows'] > 0;
    }

    public function history_results()
    {
        return displayProjectHistoryResults($this->group_id, $this->history_rows, false, $this->index);
    }

    public function row_color()
    {
        return util_get_alt_row_color($this->index++);
    }

    public function has_offset()
    {
        return $this->offset > 0;
    }

    public function offset_minus_limit()
    {
        return $this->offset - $this->limit;
    }

    public function offset_plus_limit()
    {
        return $this->offset + $this->limit;
    }

    public function previous()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'previous');
    }

    public function next()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'next');
    }

    public function max_rows_not_reached()
    {
        return ($this->offset + $this->limit) < $this->history_rows['numrows'];
    }

    public function no_change()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'no_g_change');
    }

    public function current_on_total()
    {
        return ($this->offset + $this->index - 3) . '/' . $this->history_rows['numrows'];
    }

    public function export_history()
    {
        return $GLOBALS['Language']->getText('project_admin_utils', 'export_history');
    }
}
