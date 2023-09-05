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

import type { Selection } from "d3-selection";
import { select } from "d3-selection";
import { getContainerProperties } from "./chart-badge-services";

describe("ChartBadgeServices -", () => {
    describe("getContainerProperties", () => {
        it("When there is only one digit, Then the height is equal to width", () => {
            const badge_value = 1;
            const chart = getDocument(badge_value, 5);

            const props = getContainerProperties(chart, badge_value);

            expect(props.height).toEqual(props.width);
        });

        it("When there are some digits, Then the width is biggest than height", () => {
            const badge_value = 500;
            const chart = getDocument(badge_value, 25);

            const props = getContainerProperties(chart, badge_value);

            expect(props.width).toBeGreaterThan(props.height);
        });

        it("When there is a float, Then the width is biggest than height", () => {
            const badge_value = 50.25;
            const chart = getDocument(badge_value, 25);

            const props = getContainerProperties(chart, badge_value);

            expect(props.width).toBeGreaterThan(props.height);
        });
    });

    function getDocument(
        badge_value: number,
        width: number,
    ): Selection<SVGTextElement, unknown, null, undefined> {
        const local_document = document.implementation.createHTMLDocument();
        const chart_div = local_document.createElement("svg");
        const chart = select(chart_div).append("svg").append("text").text(badge_value);

        const node = chart.node();

        if (!node) {
            throw new Error("Node doesn't exist");
        }

        node.getBBox = (): DOMRect => {
            return {
                x: 10,
                y: 10,
                width,
                height: 12,
            } as DOMRect;
        };

        return chart;
    }
});
