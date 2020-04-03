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

export class ColumnFactory {
    constructor({ x_scale, y_scale, column_width, column_height }) {
        Object.assign(this, {
            x_scale,
            y_scale,
            x_domain: x_scale.domain(),
            y_domain: y_scale.domain(),
            column_width,
            column_height,
        });
    }

    addColumn(container, date) {
        container
            .append("rect")
            .attr("class", "chart-column")
            .attr("x", () => {
                const x_position = this.x_scale(date);

                if (this.isFirstColumn(date)) {
                    return x_position;
                }

                return x_position - this.column_width / 2;
            })
            .attr("y", this.y_scale(this.y_domain[1]))
            .attr("width", () => {
                if (this.isFirstColumn(date) || this.isLastColumn(date)) {
                    return this.column_width / 2;
                }

                return this.column_width;
            })
            .attr("height", this.column_height);
    }

    isFirstColumn(date) {
        const [x_minimum] = this.x_domain;

        return date === x_minimum;
    }

    isLastColumn(date) {
        const x_maximum = this.x_domain[this.x_domain.length - 1];

        return date === x_maximum;
    }
}
