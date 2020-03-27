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

import { createBurnupChart, getTotal } from "./burnup-chart-drawer";
import { ChartPropsWhithoutTooltip } from "../../../../../../../src/www/scripts/charts-builders/type";
import {
    GenericBurnupData,
    PointsWithDateForGenericBurnup,
} from "../../../../../../agiledashboard/scripts/burnup-chart/src/type";
jest.mock("../../../../../../../src/www/scripts/charts-builders/time-scale-labels-formatter");
jest.mock("../time-scale-label-formatter");

describe("BurnupChartDrawer", () => {
    describe("getTotal", () => {
        it("When there is a highest total in points, Then it returns", () => {
            const generic: GenericBurnupData = {
                capacity: null,
                points_with_date: [
                    {
                        date: new Date().toDateString(),
                        progression: 10,
                        total: 15,
                    },
                    {
                        date: new Date().toDateString(),
                        progression: 10,
                        total: 20,
                    },
                ],
            } as GenericBurnupData;

            const total = getTotal(generic);
            expect(total).toEqual(20);
        });

        it("When points are null, Then capacity returns", () => {
            const generic: GenericBurnupData = {
                capacity: 30,
                points_with_date: [] as PointsWithDateForGenericBurnup[],
            } as GenericBurnupData;

            const total = getTotal(generic);
            expect(total).toEqual(30);
        });

        it("When points and capacity are null, Then DEFAULT_TOTAL_EFFORT returns", () => {
            const generic: GenericBurnupData = {
                capacity: null,
                points_with_date: [] as PointsWithDateForGenericBurnup[],
            } as GenericBurnupData;

            const total = getTotal(generic);
            expect(total).toEqual(5);
        });
    });
    describe("createBurnupChart", () => {
        it("When the chart is created, Then there are a G element and 2 lines scale with scale label", () => {
            const chart_svg_element = getDocument();
            createBurnupChart(chart_svg_element, getChartProps(), getGenericBurnupData());

            expect(chart_svg_element).toMatchSnapshot();
        });

        function getDocument(): HTMLElement {
            const local_document = document.implementation.createHTMLDocument();
            const chart_div = local_document.createElement("svg");
            chart_div.setAttribute("id", "chart-100");
            return chart_div;
        }

        function getChartProps(): ChartPropsWhithoutTooltip {
            return {
                graph_width: 100,
                graph_height: 100,
                margins: {
                    top: 20,
                    right: 20,
                    bottom: 20,
                    left: 20,
                },
            };
        }

        function getGenericBurnupData(): GenericBurnupData {
            return {
                opening_days: [1, 2, 3, 4, 5],
                duration: 1,
                start_date: "2019-07-01T00:00:00+00:00",
                capacity: null,
                is_under_calculation: false,
                points_with_date: getGenericMaxIs15(),
            };
        }

        function getGenericMaxIs15(): PointsWithDateForGenericBurnup[] {
            const points: PointsWithDateForGenericBurnup[] = [];
            points.push({
                date: "2019-07-01T00:00:00+00:00",
                progression: 10,
                total: 15,
            });
            points.push({
                date: "2019-07-01T00:00:00+00:00",
                progression: 12,
                total: 15,
            });

            return points;
        }
    });
});
