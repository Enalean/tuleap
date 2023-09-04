/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { ChartPropsWithRadius, DataPieChart } from "@tuleap/pie-chart";
import { getDataToDisplay, getSumOfValue, replaceValue, createPieChart } from "./pie-chart-drawer";
import { select } from "d3-selection";
import { StatisticsPieChart } from "@tuleap/pie-chart";

const mock_init = jest.fn();

jest.mock("@tuleap/pie-chart", () => {
    return {
        StatisticsPieChart: jest.fn().mockImplementation(() => {
            return {
                init: mock_init,
            };
        }),
    };
});

describe("PieChartDrawer", () => {
    beforeEach(() => jest.clearAllMocks());

    describe("createPieChart", () => {
        it("When a pie chart is created, Then StatisticsPieChart class is called", () => {
            const chart_container = getDocument();
            createPieChart(chart_container, getChartProps(), getDataPieChart());

            const args = {
                ...getChartProps(),
                data: getDataPieChartToDisplay(),
                general_prefix: "release-widget-pie-chart-ttm",
                id: "release-widget-pie-chart-ttm-100",
                prefix: "release-widget-pie-chart-ttm",
            };

            const statistic_pie_constructor = StatisticsPieChart as unknown as jest.SpyInstance;
            const statistic_pie_chart = statistic_pie_constructor.mock.results[0].value;
            expect(statistic_pie_constructor).toHaveBeenCalledWith(args);
            expect(statistic_pie_chart.init).toHaveBeenCalled();
        });
    });

    describe("getDataToDisplay", () => {
        it("When a data is under minimum, Then a bigger value is rendered", () => {
            expect(getDataToDisplay(getDataPieChart())).toEqual(getDataPieChartToDisplay());
        });
    });

    describe("getSumOfValue", () => {
        it("When there are some value, Then the sum is returned", () => {
            expect(getSumOfValue(getDataPieChart())).toBe(111);
        });

        it("When there are not some value, Then 0 is returned", () => {
            expect(getSumOfValue([])).toBe(0);
        });
    });

    describe("replaceValue", () => {
        it("When there are some data, Then values are replaces", () => {
            const document = getDocument();
            createSVGDocument(document);

            replaceValue(document, getDataPieChart());

            expect(
                select(document)
                    .select(".release-widget-pie-chart-ttm-slice-blocked")
                    .select("text")
                    .text(),
            ).toBe("10");
            expect(
                select(document)
                    .select(".release-widget-pie-chart-ttm-slice-passed")
                    .select("text")
                    .text(),
            ).toBe("1");
            expect(
                select(document)
                    .select(".release-widget-pie-chart-ttm-slice-notrun")
                    .select("text")
                    .text(),
            ).toBe("100");
            expect(
                select(document)
                    .select(".release-widget-pie-chart-ttm-slice-failed")
                    .select("text")
                    .text(),
            ).toBe("");
        });
    });

    function getDataPieChart(): DataPieChart[] {
        const data: DataPieChart[] = [];
        data.push({
            count: 10,
            key: "blocked",
            label: "Blocked",
        });
        data.push({
            count: 1,
            key: "passed",
            label: "Passed",
        });
        data.push({
            count: 100,
            key: "notrun",
            label: "Not Run",
        });
        data.push({
            count: 0,
            key: "failed",
            label: "Failed",
        });

        return data;
    }

    function getDataPieChartToDisplay(): DataPieChart[] {
        const data: DataPieChart[] = [];
        data.push({
            count: 10,
            key: "blocked",
            label: "Blocked",
        });
        data.push({
            count: 5,
            key: "passed",
            label: "Passed",
        });
        data.push({
            count: 100,
            key: "notrun",
            label: "Not Run",
        });
        data.push({
            count: 0,
            key: "failed",
            label: "Failed",
        });

        return data;
    }

    function getChartProps(): ChartPropsWithRadius {
        return {
            radius: 120,
            height: 120,
            width: 120,
        };
    }

    function getDocument(): HTMLElement {
        const local_document = document.implementation.createHTMLDocument();
        const chart_div = local_document.createElement("div");
        chart_div.setAttribute("id", "release-widget-pie-chart-ttm-100");
        return chart_div;
    }

    function createSVGDocument(chart_div: HTMLElement): void {
        select(chart_div)
            .append("g")
            .attr("class", "release-widget-pie-chart-ttm-slice-notrun")
            .append("text")
            .text("100");

        select(chart_div)
            .append("g")
            .attr("class", "release-widget-pie-chart-ttm-slice-blocked")
            .append("text")
            .text("");

        select(chart_div)
            .append("g")
            .attr("class", "release-widget-pie-chart-ttm-slice-failed")
            .append("text")
            .text("0");

        select(chart_div)
            .append("g")
            .attr("class", "release-widget-pie-chart-ttm-slice-passed")
            .append("text")
            .text("4");
    }
});
