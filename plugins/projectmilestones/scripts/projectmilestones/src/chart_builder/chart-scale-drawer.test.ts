/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { addScaleLines } from "./chart-scale-drawer";
import { Selection, select } from "d3-selection";

describe("ScaleDrawer -", () => {
    describe("addScaleLines -", () => {
        it("When the scales are created, Then there are 2 line element with their attributes", () => {
            const chart_div = getDocument();

            const coordinate = {
                x_coordinate_minimum: 0,
                y_coordinate_minimum: 100,
                x_coordinate_maximum: 200,
                y_coordinate_maximum: 500,
            };

            addScaleLines(chart_div, coordinate);

            expect(chart_div).toMatchSnapshot();
        });
    });

    function getDocument(): Selection<SVGSVGElement, unknown, null, undefined> {
        const local_document = document.implementation.createHTMLDocument();
        const chart_div = local_document.createElement("svg");
        return select(chart_div).append("svg").attr("width", 100).attr("height", 100);
    }
});
