<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2003 Richard Heyes                                 |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// +-----------------------------------------------------------------------+
//
// $Id: Table.php,v 1.8 2005/07/16 16:54:44 richard Exp $
//
// Utility for printing tables from cmdline scripts
//

define('CONSOLE_TABLE_HORIZONTAL_RULE', 1);
define('CONSOLE_TABLE_ALIGN_LEFT', -1);
define('CONSOLE_TABLE_ALIGN_CENTER', 0);
define('CONSOLE_TABLE_ALIGN_RIGHT', 1);

class Console_Table
{
    /**
    * The table headers
    * @var array
    */
    var $_headers;


    /**
    * The data of the table
    * @var array
    */
    var $_data;


    /**
    * The max number of columns in a row
    * @var integer
    */
    var $_max_cols;


    /**
    * The max number of rows in the table
    * @var integer
    */
    var $_max_rows;


    /**
    * Lengths of the columns, calculated
    * when rows are added to the table.
    * @var array
    */
    var $_cell_lengths;


    /**
    * Some options that configure various
    * things
    * @var array;
    */
    var $_options;


    /**
    * How many spaces to use to pad the table
    * @var integer
    */
    var $_padding;


    /**
    * Column filters
    * @var array
    */
    var $_filters;


    /**
    * Columns to calculate totals for
    * @var array
    */
    var $_calculateTotals;


    /**
    * Alignment of the columns
    * @var array
    */
    var $_col_align;


    /**
    * Default alignment of columns
    * @var int
    */
    var $_defaultAlign;


    /**
    * Constructor
    *
    * @param int $align Default alignment
    */
    function Console_Table($align = CONSOLE_TABLE_ALIGN_LEFT)
    {
        $this->_headers      = array();
        $this->_data         = array();
        $this->_cell_lengths = array();
        $this->_max_cols     = 0;
        $this->_max_rows     = 0;
        $this->_padding      = 1;
        $this->_filters      = array();
        $this->_col_align    = array();
        $this->_defaultAlign = $align;
    }


    /**
    * Converts an array to a table. Must be a two dimensional array.
    * (Static)
    *
    * @param array $headers      Headers for the table
    * @param array $data         Data for the table
    * @param bool  $returnObject Whether to return the Console_Table object (default: No)
    */
    function fromArray($headers, $data, $returnObject = false)
    {
        if (!is_array($headers) OR !is_array($data)) {
            return false;
        }

        $table = &new Console_Table();
        $table->setHeaders($headers);

        foreach ($data as $row) {
            $table->addRow(array_values($row));
        }

        return $returnObject ? $table : $table->getTable();
    }


    /**
    * Adds a filter to the object. Filters are standard php callbacks which are
    * run on the data before table generation is performed. Filters are applied
    * in the order they're added. the callback function must accept a single
    * argument, which is a single table cell.
    *
    * @param int      $col      Column to apply filter to
    * @param callback $callback PHP callback to apply
    */
    function addFilter($col, &$callback)
    {
        $this->_filters[] = array($col, &$callback);
    }


    /**
    * Sets the alignment for the columns
    * @param integer $col_id Column ID
    * @param integer $align  Alignment to set for this column
    *                        -1 = left, 0 = center, 1 = right
    *                       OR use the constants:
    *                      CONSOLE_TABLE_ALIGN_LEFT
    *                      CONSOLE_TABLE_ALIGN_CENTER
    *                      CONSOLE_TABLE_ALIGN_RIGHT
    */
    function setAlign($col_id, $align = CONSOLE_TABLE_ALIGN_LEFT)
    {
        // -1 = left, 0 = center, 1 = right
        if ($align == CONSOLE_TABLE_ALIGN_CENTER) {
            $pad = STR_PAD_BOTH;

        } elseif ($align == CONSOLE_TABLE_ALIGN_RIGHT) {
            $pad = STR_PAD_LEFT;

        } else {
            $pad = STR_PAD_RIGHT;
        }
        $this->_col_align[$col_id] = $pad;
    }


    /**
    * Specifies which columns are to have totals calculated for them and
    * added as a new row at the bottom.
    *
    * @param array $cols Array of column IDs (0 is first column, 1 is second etc)
    */
    function calculateTotalsFor($cols)
    {
        $this->_calculateTotals = $cols;
    }


    /**
    * Sets the headers for the columns
    *
    * @param array $headers The column headers
    */
    function setHeaders($headers)
    {
        $this->_headers = $headers;
        $this->_updateRowsCols($headers);
    }


    /**
    * Adds a row to the table
    *
    * @param array $row    The row data to add
    * @param array $append Whether to append or prepend the row
    */
    function addRow($row, $append = true)
    {
        $append ? $this->_data[] = array_values($row) : array_unshift($this->_data, array_values($row));

        $this->_updateRowsCols($row);
    }


    /**
    * Inserts a row after a given row number in the table. If $row_id
    * is not given it will prepend the row.
    *
    * @param array   $row    The data to insert
    * @param integer $row_id Row number to insert before
    */
    function insertRow($row, $row_id = 0)
    {
        array_splice($this->_data, $row_id, 0, array($row));

        $this->_updateRowsCols($row);
    }


    /**
    * Adds a column to the table
    *
    * @param array   $col_data The data of the column. Can be numeric or associative array
    * @param integer $col_id   The column index to populate
    * @param integer $row_id   If starting row is not zero, specify it here
    */
    function addCol($col_data, $col_id = 0, $row_id = 0)
    {
        foreach ($col_data as $col_cell) {
            $this->_data[$row_id++][$col_id] = $col_cell;
        }

        $this->_updateRowsCols();
        $this->_max_cols = max($this->_max_cols, $col_id + 1);
    }


    /**
    * Adds data to the table. Argument should be
    * a two dimensional array containing the data
    * to be added.
    *
    * @param array   $data   The data to add to the table
    * @param integer $col_id Optional starting column ID
    * @param integer $row_id Optional starting row ID
    */
    function addData($data, $col_id = 0, $row_id = 0)
    {
        foreach ($data as $row) {
            $starting_col = $col_id;
            foreach ($row as $cell) {
                $this->_data[$row_id][$starting_col++] = $cell;
            }
            $this->_updateRowsCols();
            $this->_max_cols = max($this->_max_cols, $starting_col);
            $row_id++;
        }
    }


    /**
    * Adds a Horizontal Seperator to the table
    */
    function addSeparator()
    {
        $this->_data[] = CONSOLE_TABLE_HORIZONTAL_RULE;
    }


    /**
    * Returns the table in wonderful
    * ASCII art
    */
    function getTable()
    {
        $this->_applyFilters();
        $this->_calculateTotals();
        $this->_validateTable();

        return $this->_buildTable();
    }


    /**
    * Calculates totals for columns
    */
    function _calculateTotals()
    {
        if (!empty($this->_calculateTotals)) {

            $this->addSeparator();

            $totals = array();

            foreach ($this->_data as $row) {
                if (is_array($row)) {
                    foreach ($this->_calculateTotals as $columnID) {
                        $totals[$columnID] += $row[$columnID];
                    }
                }
            }

            $this->_data[] = $totals;
            $this->_updateRowsCols();
        }
    }


    /**
    * Applies any column filters to the data
    */
    function _applyFilters()
    {
        if (!empty($this->_filters)) {
            foreach ($this->_filters as $filter) {
                $column   = $filter[0];
                $callback = $filter[1];

                foreach ($this->_data as $row_id => $row_data) {
                    $this->_data[$row_id][$column] = call_user_func($callback, $row_data[$column]);
                }
            }
        }
    }


    /**
    * Ensures column and row counts are correct
    */
    function _validateTable()
    {
        for ($i=0; $i<$this->_max_rows; $i++) {
            for ($j=0; $j<$this->_max_cols; $j++) {
                if (!isset($this->_data[$i][$j]) AND $this->_data[$i] != CONSOLE_TABLE_HORIZONTAL_RULE) {
                    $this->_data[$i][$j] = '';
                }

                // Update cell lengths
                $this->_calculateCellLengths($this->_data[$i]);
            }

            if ($this->_data[$i] != CONSOLE_TABLE_HORIZONTAL_RULE) {
                 ksort($this->_data[$i]);
            }

        }

        ksort($this->_data);
    }


    /**
    * Builds the table
    */
    function _buildTable()
    {
        $return = array();
        $rows   = $this->_data;

        for ($i=0; $i<count($rows); $i++) {
            for ($j=0; $j<count($rows[$i]); $j++) {
                if ($rows[$i] != CONSOLE_TABLE_HORIZONTAL_RULE AND strlen($rows[$i][$j]) < $this->_cell_lengths[$j]) {
                    $rows[$i][$j] = str_pad($rows[$i][$j], $this->_cell_lengths[$j], ' ', $this->_col_align[$j]);
                }
            }

            if ($rows[$i] != CONSOLE_TABLE_HORIZONTAL_RULE) {
                $row_begin    = '|' . str_repeat(' ', $this->_padding);
                $row_end      = str_repeat(' ', $this->_padding) . '|';
                $implode_char = str_repeat(' ', $this->_padding) . '|' . str_repeat(' ', $this->_padding);
                $return[] = $row_begin . implode($implode_char, $rows[$i]) . $row_end;
            } else {
                $return[] = $this->_getSeparator();
            }

        }

        $return = $this->_getSeparator() . "\r\n" . implode("\n", $return) . "\r\n" . $this->_getSeparator() . "\r\n";

        if (!empty($this->_headers)) {
            $return = $this->_getHeaderLine() .  "\r\n" . $return;
        }

        return $return;
    }


    /**
    * Creates a horizontal separator for header
    * separation and table start/end etc
    *
    */
    function _getSeparator()
    {
        foreach ($this->_cell_lengths as $cl) {
            $return[] = str_repeat('-', $cl);
        }

        $row_begin    = '+' . str_repeat('-', $this->_padding);
        $row_end      = str_repeat('-', $this->_padding) . '+';
        $implode_char = str_repeat('-', $this->_padding) . '+' . str_repeat('-', $this->_padding);

        $return = $row_begin . implode($implode_char, $return) . $row_end;

        return $return;
    }


    /**
    * Returns header line for the table
    */
    function _getHeaderLine()
    {
        // Make sure column count is correct
        for ($i=0;  $i<$this->_max_cols; $i++) {
            if (!isset($this->_headers[$i])) {
                $this->_headers[$i] = '';
            }
        }

        for ($i=0; $i<count($this->_headers); $i++) {
            if (strlen($this->_headers[$i]) < $this->_cell_lengths[$i]) {
                $this->_headers[$i] = str_pad($this->_headers[$i], $this->_cell_lengths[$i], ' ', $this->_col_align[$i]);
            }
        }

        $row_begin    = '|' . str_repeat(' ', $this->_padding);
        $row_end      = str_repeat(' ', $this->_padding) . '|';
        $implode_char = str_repeat(' ', $this->_padding) . '|' . str_repeat(' ', $this->_padding);

        $return[] = $this->_getSeparator();
        $return[] = $row_begin . implode($implode_char, $this->_headers) . $row_end;

        return implode("\r\n", $return);
    }


    /**
    * Update max cols/rows
    */
    function _updateRowsCols($rowdata = null)
    {
        // Update max cols
        $this->_max_cols = max($this->_max_cols, count($rowdata));

        // Update max rows
        ksort($this->_data);
        $this->_max_rows = end(array_keys($this->_data)) + 1;

        switch ($this->_defaultAlign) {
            case CONSOLE_TABLE_ALIGN_CENTER: $pad = STR_PAD_BOTH; break;
            case CONSOLE_TABLE_ALIGN_RIGHT:  $pad = STR_PAD_LEFT; break;
            default:
            case CONSOLE_TABLE_ALIGN_LEFT:   $pad = STR_PAD_RIGHT; break;
        }

        // Set default column alignments
        for ($i = count($this->_col_align); $i<$this->_max_cols; $i++) {
            $this->_col_align[$i] = $pad;
        }

    }


    /**
    * This function given a row of data will
    * calculate the max length for each column
    * and store it in the _cell_lengths array.
    *
    * @param array $row The row data
    */
    function _calculateCellLengths($row)
    {
        for ($i=0; $i<count($row); $i++) {
            $this->_cell_lengths[$i] = max(strlen(@$this->_headers[$i]), @$this->_cell_lengths[$i], strlen(@$row[$i]));
        }
    }
}
?>