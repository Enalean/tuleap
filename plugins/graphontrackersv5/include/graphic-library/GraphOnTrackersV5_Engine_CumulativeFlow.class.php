<?php
/*
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2013. Jtekt Europe SAS.
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

class GraphOnTrackersV5_Engine_CumulativeFlow extends GraphOnTrackersV5_Engine
{
    public $scale;
    public $stop_date;
    public $start_date;
    public $color_set;
    public $keys;
    public $nbOpt;
    public $error;
    public $title;
    public $height;
    public $width;
    public $data;

    public function validData()
    {
        if (! $this->hasStart()) {
            $this->setError(
                dgettext('tuleap-graphontrackersv5', "No start date defined.")
            );

            return true;
        }

        if (! $this->isStartDateBeforeEndDate()) {
            $this->setError(
                dgettext('tuleap-graphontrackersv5', "Start date must be before the end date.")
            );

            return true;
        }

        if (! $this->hasData()) {
            if (! $this->hasError()) {
                $this->setError(
                    dgettext('tuleap-graphontrackersv5', 'No data to display.')
                );
            }

            return true;
        }

        return ! $this->hasError();
    }

    /**
     * @return null
     */
    public function buildGraph()
    {
        return null;
    }

    private function hasData()
    {
        if ($this->data === null || count($this->data) === 0) {
            return false;
        }

        $sumData = 0;

        foreach ($this->data as $row) {
            $sumData += count($row);
        }
        return (count(reset($this->data)) > 0) && $sumData > 0;
    }

    private function hasError()
    {
        return $this->error !== null;
    }

    private function hasStart()
    {
        return $this->start_date && $this->start_date > 0;
    }

    private function hasEnd()
    {
        return $this->stop_date && $this->stop_date > 0;
    }

    private function isStartDateBeforeEndDate()
    {
        if (! $this->hasEnd()) {
            return true;
        }

        return $this->start_date <= $this->stop_date;
    }

    public function toArray()
    {
        return parent::toArray() + [
            'type'   => 'cumulativeflow',
            'height' => $this->height,
            'width'  => $this->width,
            'data'   => $this->data,
            'error'  => $this->error
        ];
    }

    public function setError($error)
    {
        $this->error = [
            "message" => dgettext('tuleap-graphontrackersv5', 'Unable to render the chart'),
            "cause" => $error
        ];
    }
}
