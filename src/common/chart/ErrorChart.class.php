<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Chart\Chart;

/**
 * This class is responsible of displaying an error message as an image
 */
class ErrorChart extends Chart
{

    /**
     * @var int the width of the chart
     */
    private $img_width;

    /**
     * @param string $title   The main title of the error. eg: "Unable to render the chart"
     * @param string $msg     The error message full of details
     * @param int    $aWidth  The width of the image (this forces the text to wrap)
     *                        /!\ A too small width may lead to a jpgraph error
     * @param int    $aHeight The height of the image
     *                        /!\ A too small width may lead to a jpgraph error
     */
    public function __construct($title, $msg, $aWidth = 600, $aHeight = 400)
    {
        parent::__construct($aWidth, $aHeight);
        $this->img_width = $aWidth;
        $this->jpgraph_instance->InitFrame();

        $padding = 10;

        $graph_title = $this->addTextToGraph($title, $padding, $padding, FS_BOLD, 12, $aWidth);

        $height = $graph_title->GetTextHeight($this->jpgraph_instance->img);
        $text   = $this->addTextToGraph($msg, $padding, 2 * $padding + $height, FS_NORMAL, 8, $aWidth);
    }

    /**
     * @return Text
     */
    private function addTextToGraph($msg, $padding_left, $padding_top, $font_weight, $font_size, $img_width)
    {
        $text = new Text($msg, $padding_left, $padding_top);
        $text->SetFont($this->getFont(), $font_weight, $font_size);
        $text->SetColor($this->getMainColor());

        //word wrap
        $width = $text->GetWidth($this->jpgraph_instance->img) - $padding_left;
        $text->SetWordWrap(floor(strlen($msg) * ($this->img_width - 3 * $padding_left) / $width));

        $text->Stroke($this->jpgraph_instance->img);
        return $text;
    }

    protected function getGraphClass(): string
    {
        return \CanvasGraph::class;
    }
}
