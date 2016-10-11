<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\BotMattermostAgileDashboard\SenderServices;

class MarkdownFormatter
{

    public function addTitleOfLevel($title, $title_level)
    {
        $level = '##### ';
        if ($title_level == 5) {
            $level = '##### ';
        } else if ($title_level == 4) {
            $level = '#### ';
        } else if ($title_level == 3) {
            $level = '### ';
        }

        return $this->addLineOfText($level.$title);
    }

    public function createSimpleTableText(array $infos)
    {
        $table_header = '';
        $interline    = '';
        $table_body   = '';
        $end_of_line  = '|'.PHP_EOL;
        foreach ($infos as $info_name => $info_value) {
            $table_header .= '| '.$info_name.' ';
            $interline    .= '| :--';
            $table_body   .= '| '.$info_value.' ';
        }
        $table = $table_header.$end_of_line.
            $interline.$end_of_line.
            $table_body.$end_of_line;

        return $table;
    }

    public function addLineOfText($text)
    {
        return $text.PHP_EOL;
    }

    public function createTableText(array $table_header, array $table_body)
    {
        $table_text_header = '';
        $interline    = '';
        foreach ($table_header as $column_name) {
            $table_text_header .= '| '.$column_name.' ';
            $interline    .= '| :--';
        }
        $table = $this->addOneRowOfTable($table_text_header);
        $table .= $this->addOneRowOfTable($interline);
        foreach ($table_body as $row) {
            $table_text_body   = '';
            foreach($row as $column_value) {
                $table_text_body .= '| '.$column_value.' ';
            }
            $table .= $this->addOneRowOfTable($table_text_body);
        }

        return $table;
    }

    private function addOneRowOfTable($new_row)
    {
        $end_of_line  = '|'.PHP_EOL;
        return $new_row.$end_of_line;
    }

    public function addSeparationLine()
    {
        return $this->addLineOfText("***");
    }
}
