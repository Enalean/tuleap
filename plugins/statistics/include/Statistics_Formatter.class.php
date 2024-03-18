<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
 * Render statistics in csv format
 */
class Statistics_Formatter
{
    private $separator;
    public string $startDate;
    public string $endDate;
    public ?int $groupId = null;
    private $csv_handle;

    public function __construct($startDate, $endDate, $separator, $groupId = null)
    {
        $this->separator  = $separator;
        $this->csv_handle = fopen('php://memory', 'w+');
        $this->startDate  = $startDate;
        $this->endDate    = $endDate;
        $this->groupId    = $groupId;
        $this->addEmptyLine();
    }

    /**
     * Add a line to the content
     *
     * @param array $line Array containing the elements of a csv line
     *
     * @return void
     */
    public function addLine($line)
    {
        fputcsv($this->csv_handle, $line, $this->separator);
    }

    /**
     * Add a header to the content
     *
     * @param String $title String containing the heading element
     *
     * @return void
     */
    public function addHeader($title)
    {
        $this->addLine([$title]);
    }

    /**
     * Add an empty line to the content
     *
     * @return void
     */
    public function addEmptyLine()
    {
        fwrite($this->csv_handle, "\n");
    }

    /**
     * Reset the content
     *
     * @return void
     */
    public function clearContent()
    {
        ftruncate($this->csv_handle, 0);
    }

    /**
     * Obtain statistics in csv format
     *
     * @return String
     */
    public function getCsvContent()
    {
        rewind($this->csv_handle);
        return stream_get_contents($this->csv_handle);
    }
}
