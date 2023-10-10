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
import * as chart_badge_services from "./chart-badge-services";
import { addBadgeCaption } from "./chart-badge-generator";
import type { Selection } from "d3-selection";
import { select } from "d3-selection";
import type { XYSizeElement } from "../type";

jest.mock("./chart-badge-services", () => ({
    getContainerProperties: jest.fn((): XYSizeElement => {
        return { x: 10, y: 10, width: 10, height: 10 };
    }),
}));

describe("ChartBadgeGenerator -", () => {
    it("When the badge is created, Then value of remaining effort is displayed in the front of the badge", () => {
        const doc = document.implementation.createHTMLDocument();
        const chart_div = doc.createElement("svg");
        const chart_svg = getSelectionSVG(chart_div);

        addBadgeCaption(10, 10, 1, chart_svg, 103);

        expect(chart_badge_services.getContainerProperties).toHaveBeenCalled();
        expect(chart_div).toMatchSnapshot();
    });

    function getSelectionSVG(
        chart_div: HTMLElement,
    ): Selection<SVGSVGElement, unknown, null, undefined> {
        return select(chart_div).append("svg").attr("width", 100).attr("height", 100);
    }
});
