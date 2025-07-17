/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
// @vitest-environment jsdom
import { expect, describe, it, beforeEach } from "vitest";
import type { PieChartArgs } from "../stories/graphs/pie-chart.stories";
import { default as PieChartMeta } from "../stories/graphs/pie-chart.stories";

describe("PieChart Storybook snapshot", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        const chart_element = PieChartMeta.render(PieChartMeta.args as PieChartArgs, {
            globals: {},
            parameters: {},
            args: {
                width: PieChartMeta.args.width,
                height: PieChartMeta.args.height,
                data: PieChartMeta.args.data,
                prefix: PieChartMeta.args.prefix,
                general_prefix: PieChartMeta.args.general_prefix,
            },
            viewMode: "story",
            loaded: undefined,
            abortSignal: undefined,
            canvasElement: undefined,
            hooks: undefined,
            originalStoryFn: undefined,
            step: undefined,
            context: undefined,
            canvas: undefined,
            mount: undefined,
            reporting: undefined,
            initialArgs: undefined,
            argTypes: undefined,
            componentId: "",
            title: "",
            kind: "",
            id: "",
            name: "",
            story: "",
            tags: [],
        }) as HTMLElement;
        document.body.appendChild(chart_element);
    });
    it("matches snapshot of pie-chart-container", () => {
        const container = document.body.querySelector(".pie-chart-container");
        expect(container.outerHTML).toMatchSnapshot();
    });
    it("matches snapshot of the legend", () => {
        const legend = document.body.querySelector(".my-legend-legend");
        expect(legend.outerHTML).toMatchSnapshot();
    });
});
