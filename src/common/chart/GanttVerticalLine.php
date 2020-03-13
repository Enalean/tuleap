<?php
/**
 * Copyright (c) Asial Corporation. All rights reserved.
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\chart;

use GanttPlotObject;
use JpGraphError;
use LineProperty;
use TextPropertyBelow;

final class GanttVerticalLine extends GanttPlotObject
{
    private $iLine;
    private $title_margin = 3;
    private $iDayOffset = 0.5;
    private $iStartRow = -1;
    private $iEndRow = -1;

    public function __construct($aDate, $aTitle, $aColor, $aWeight, $aStyle)
    {
        parent::__construct();
        $this->iLine = new LineProperty();
        $this->iLine->SetColor($aColor);
        $this->iLine->SetWeight($aWeight);
        $this->iLine->SetStyle($aStyle);
        $this->iLine->Show();
        $this->iStart = $aDate;
        $this->title  = new TextPropertyBelow();
        $this->title->Set($aTitle);
    }

    public function SetRowSpan($aStart, $aEnd = -1) // phpcs:ignore
    {
        $this->iStartRow = $aStart;
        $this->iEndRow = $aEnd;
    }

    public function SetDayOffset($aOff = 0.5) // phpcs:ignore
    {
        if ($aOff < 0.0 || $aOff > 1.0) {
            JpGraphError::RaiseL(6029);
            //("Offset for vertical line must be in range [0,1]");
        }
        $this->iDayOffset = $aOff;
    }

    public function SetTitleMargin($aMarg) // phpcs:ignore
    {
        $this->title_margin = $aMarg;
    }

    public function SetWeight($aWeight) // phpcs:ignore
    {
        $this->iLine->SetWeight($aWeight);
    }

    public function Stroke($aImg, $aScale) // phpcs:ignore
    {
        $d = $aScale->NormalizeDate($this->iStart);
        if ($d < $aScale->iStartDate || $d > $aScale->iEndDate) {
            return;
        }
        if ($this->iDayOffset != 0.0) {
            $d += 24 * 60 * 60 * $this->iDayOffset;
        }
        $x = $aScale->TranslateDate($d);//d=1006858800,

        if ($this->iStartRow > -1) {
            $y1 = $aScale->TranslateVertPos($this->iStartRow, true);
        } else {
            $y1 = $aScale->iVertHeaderSize + $aImg->top_margin;
        }

        if ($this->iEndRow > -1) {
            $y2 = $aScale->TranslateVertPos($this->iEndRow);
        } else {
            $y2 = $aImg->height - $aImg->bottom_margin;
        }

        $this->iLine->Stroke($aImg, $x, $y1, $x, $y2);
        $this->title->Align('center', 'top');
        $this->title->Stroke($aImg, $x, $y2 + $this->title_margin);
    }
}
