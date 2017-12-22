/**
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
import { selectAll }              from 'd3-selection';
import { symbol, symbolTriangle } from 'd3-shape';

export class TooltipFactory {
    constructor({
        tooltip_margin_bottom,
        tooltip_padding_width,
        tooltip_padding_height,
        tooltip_arrow_size,
        tooltip_font_size
    }) {
        Object.assign(this, {
            tooltip_width : 0,
            tooltip_height: 0,
            tooltip_middle: 0,
            tooltip_margin_bottom,
            tooltip_padding_width,
            tooltip_padding_height,
            tooltip_arrow_size,
            tooltip_font_size
        });
    }
    addTooltip(column) {
        this.column         = column;
        this.tooltip_target = column.select('.chart-tooltip-target');
        this.tooltip        = column.append('g').attr('class', 'chart-tooltip');
        this.arrow          = this.getTooltipArrow();
        this.bubble         = this.tooltip.append('rect').attr('class', 'chart-tooltip-bubble');
        this.bubble_arrow   = this.tooltip.append('g');
        this.tooltip_text   = this.tooltip.append('text')
            .attr('y', 0)
            .attr('class', 'chart-tooltip-text')
            .attr('font-size', this.tooltip_font_size);

        this.bubble_arrow.append('path')
            .attr('d', this.arrow())
            .attr('class', 'chart-tooltip-bubble')
            .attr('transform', 'rotate(180)');

        this.resizeTheBubble();
        this.positionBubble();

        return this;
    }

    static removeTooltips() {
        selectAll('.chart-tooltip').remove();
    }

    getTooltipWidth() {
        return this.tooltip_text.node().getBBox().width + 2 * this.tooltip_padding_width;
    }

    getTooltipHeight() {
        return this.getTooltipTextHeight() + 2 * this.tooltip_padding_height;
    }

    getTooltipTextHeight() {
        return this.tooltip_text.node().getBBox().height;
    }

    getTooltipCoordinates() {
        const { x, y, width } = this.tooltip_target.node().getBBox();

        return {
            tooltip_x: x - this.tooltip_middle + width / 2,
            tooltip_y: y - this.tooltip_margin_bottom - this.tooltip_height
        };
    }

    getTooltipArrow() {
        return symbol().type(symbolTriangle).size(this.tooltip_arrow_size);
    }

    resizeTheBubble() {
        this.tooltip_width  = this.getTooltipWidth();
        this.tooltip_height = this.getTooltipHeight();
        this.tooltip_middle = this.tooltip_width / 2;

        this.bubble.attr('width', this.tooltip_width)
            .attr('height', this.tooltip_height)
            .attr('rx', 3);

        this.bubble_arrow
            .attr(
                'transform',
                `translate(
                    ${ this.tooltip_middle },
                    ${ this.tooltip_height }
                )`
            );
    }

    centerText() {
        this.tooltip_text.selectAll('tspan')
            .attr('x', this.tooltip_middle);
    }

    positionBubble() {
        const { tooltip_x, tooltip_y } = this.getTooltipCoordinates();

        this.tooltip.attr('transform', `translate(${ tooltip_x }, ${ tooltip_y })`);

        this.column.raise();
    }

    addTextLine(text) {
        this.tooltip_text
            .append('tspan')
            .attr('text-anchor', 'middle')
            .attr('dy', 15)
            .text(text);

        this.centerText();
        this.resizeTheBubble();
        this.positionBubble();

        return this;
    }
}
