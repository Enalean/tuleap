/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Meta, StoryObj } from "@storybook/web-components";
import type { DataPieChart } from "@tuleap/pie-chart";
import { createPieChartElement } from "@tuleap/plugin-graphs";
import "./pie-chart.scss";

export interface PieChartArgs {
    width: number;
    height: number;
    first_slice_label: string;
    first_slice_count: number;
    first_slice_color: string;
    number_of_slices: number;
}

const available_colors = [
    "tlp-swatch-inca-silver",
    "tlp-swatch-chrome-silver",
    "tlp-swatch-firemist-silver",
    "tlp-swatch-red-wine",
    "tlp-swatch-fiesta-red",
    "tlp-swatch-coral-pink",
    "tlp-swatch-teddy-brown",
    "tlp-swatch-clockwork-orange",
    "tlp-swatch-graffiti-yellow",
    "tlp-swatch-army-green",
    "tlp-swatch-neon-green",
    "tlp-swatch-acid-green",
    "tlp-swatch-sherwood-green",
    "tlp-swatch-ocean-turquoise",
    "tlp-swatch-surf-green",
    "tlp-swatch-deep-blue",
    "tlp-swatch-lake-placid-blue",
    "tlp-swatch-daphne-blue",
    "tlp-swatch-plum-crazy",
    "tlp-swatch-ultra-violet",
    "tlp-swatch-lilac-purple",
    "tlp-swatch-panther-pink",
    "tlp-swatch-peggy-pink",
    "tlp-swatch-flamingo-pink",
    "tlp-danger-color",
    "tlp-success-color",
    "tlp-dimmed-color",
    "tlp-info-color",
    "tlp-warning-color",
];

const meta: Meta<PieChartArgs> = {
    title: "graphs/PieChart",
    render: (args: PieChartArgs) => {
        const data = generatePieData(args);

        return createPieChartElement({
            id: "storybook-piechart",
            width: args.width,
            height: args.height,
            data,
            language: "en_US",
            prefix: "my-pie",
            general_prefix: "my-legend",
        });
    },
    argTypes: {
        first_slice_label: {
            control: "text",
        },
        first_slice_count: {
            control: "number",
        },
        first_slice_color: {
            control: { type: "select" },
            options: available_colors,
        },
        number_of_slices: {
            control: { type: "range", min: 1, max: 10 },
        },
    },
    args: {
        width: 400,
        height: 300,
        first_slice_label: "Label 1",
        first_slice_count: 30,
        first_slice_color: "tlp-swatch-flamingo-pink",
        number_of_slices: 3,
    },
};
export default meta;

function generatePieData(args: PieChartArgs): DataPieChart[] {
    if (args.number_of_slices === 0) {
        return [];
    }
    const data: DataPieChart[] = [];
    data.push({
        key: "a",
        label: args.first_slice_label,
        count: args.first_slice_count,
        color: args.first_slice_color,
    });
    for (let i = 1; i < args.number_of_slices; i++) {
        data.push({
            key: String(i + 1),
            label: `Label ${i + 1}`,
            count: 10 + i * 5,
            color: available_colors[i - 1],
        });
    }

    return data;
}

export const PieChart: StoryObj<PieChartArgs> = {};

export const EmptyState: StoryObj<PieChartArgs> = {
    args: {
        number_of_slices: 0,
        first_slice_label: "",
        first_slice_count: 0,
        first_slice_color: "tlp-swatch-flamingo-pink",
    },
};
