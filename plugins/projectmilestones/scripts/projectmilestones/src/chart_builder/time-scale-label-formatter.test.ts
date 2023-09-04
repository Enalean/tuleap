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
import { removeAllLabelsOverlapsOthersLabels } from "./time-scale-label-formatter";

describe("BurndownTimeScaleLabelFormatter -", () => {
    it("When no ticks overlaps others ticks, Then there are all ticks", () => {
        const chart = getChart();

        addTick(chart, 10, "Aug 2019", 0, 20);
        addTick(chart, 15, "Sept 2019", 25, 45);
        addTick(chart, 15, "Oct 2019", 50, 70);

        let displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(3);

        removeAllLabelsOverlapsOthersLabels(chart);

        displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(3);
    });

    it("When some ticks overlaps others ticks, Then there are deleted", () => {
        const chart = getChart();

        addTick(chart, 10, "Aug 2019", 0, 20);
        addTick(chart, 15, "Sept 2019", 15, 30);
        addTick(chart, 15, "Oct 2019", 25, 40);

        let displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(3);

        removeAllLabelsOverlapsOthersLabels(chart);

        displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(2);
    });

    it("When 2 ticks don't touch each other, Then no ticks are removed", () => {
        const chart = getChart();

        addTick(chart, 10, "Aug 2019", 0, 20);
        addTick(chart, 15, "Sept 2019", 30, 50);

        let displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(2);

        removeAllLabelsOverlapsOthersLabels(chart);

        displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(2);
    });

    it("When 3 ticks touch each other, Then 1 tick is removed", () => {
        const chart = getChart();

        addTick(chart, 10, "Aug 2019", 0, 20);
        addTick(chart, 15, "Sept 2019", 10, 20);
        addTick(chart, 15, "Oct 2019", 20, 30);

        let displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(3);

        removeAllLabelsOverlapsOthersLabels(chart);

        displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(1);
    });

    it("When 3 ticks touch each other and another ticks further, Then 1 tick is removed", () => {
        const chart = getChart();

        addTick(chart, 10, "Aug 2019", 0, 20);
        addTick(chart, 15, "Sept 2019", 10, 20);
        addTick(chart, 15, "Oct 2019", 20, 30);
        addTick(chart, 15, "Nov 2019", 30, 40);

        let displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(4);

        removeAllLabelsOverlapsOthersLabels(chart);

        displayed_ticks = chart.selectAll(`.chart-x-axis > .tick`).nodes();
        expect(displayed_ticks).toHaveLength(2);
    });

    function addTick(
        svg: Selection<SVGSVGElement, unknown, null, undefined>,
        transform_x: number,
        date: string,
        left_padding: number,
        right_padding: number,
    ): void {
        const g_element = svg
            .append("g")
            .attr("class", "tick")
            .attr("opacity", "1")
            .attr("transform", "translate(" + transform_x + ",0)");

        const text_element = g_element
            .append("text")
            .attr("fill", "currentColor")
            .attr("y", "11")
            .attr("dy", "0.71em");

        const node = g_element.node();

        if (!node) {
            throw new Error("Node doesn't exist");
        }

        node.getBoundingClientRect = (): DOMRect => {
            return {
                left: left_padding,
                right: right_padding,
            } as DOMRect;
        };

        text_element.text(date);
    }

    function getDocument(): HTMLElement {
        const local_document = document.implementation.createHTMLDocument();
        return local_document.createElement("svg");
    }

    function getChart(): Selection<SVGSVGElement, unknown, null, undefined> {
        const svg = select(getDocument()).append("svg");
        return svg.append("svg").attr("class", "chart-x-axis");
    }
});
