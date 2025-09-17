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

import { beforeEach, describe, expect, it } from "vitest";
import { createPieChartElement } from "./pie-chart";

describe(`PieChart`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();

        const chart_element = createPieChartElement({
            id: "piechart",
            width: 400,
            height: 300,
            data: [
                { key: "a", label: "Label 1", count: 30, color: "tlp-swatch-flamingo-pink" },
                { key: "2", label: "Label 2", count: 15, color: "tlp-swatch-inca-silver" },
                { key: "3", label: "Label 3", count: 20, color: "tlp-swatch-chrome-silver" },
            ],
            language: "en_US",
            prefix: "my-pie",
            general_prefix: "my-legend",
        });
        doc.body.append(chart_element);
    });

    it(`matches snapshot of pie-chart-container and legend`, () => {
        const container = doc.body.querySelector(".pie-chart-container");
        expect(container?.outerHTML).toMatchSnapshot();
        const legend = doc.body.querySelector(".my-legend-legend");
        expect(legend?.outerHTML).toMatchSnapshot();
    });
});
