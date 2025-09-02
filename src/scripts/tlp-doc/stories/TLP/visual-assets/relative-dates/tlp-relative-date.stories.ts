/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { Meta, StoryObj } from "@storybook/web-components-vite";
import { html, type TemplateResult } from "lit";
import "@tuleap/tlp-relative-date";

type RelativeDatesProps = {
    table: boolean;
};

function getDates(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-property">
    <label class="tlp-label">Date in french (relative, on the right)</label>
    <tlp-relative-date
        date="2020-07-13T14:16:04.467Z"
        absolute-date="13/07/2020 16:16"
        placement="right"
        preference="relative"
        locale="fr_FR"
    >13/07/2020 16:16</tlp-relative-date>
</div>

<div class="tlp-property">
    <label class="tlp-label">Date in english (absolute, on the right)</label>
    <tlp-relative-date
        date="2020-07-13T14:16:04.467Z"
        absolute-date="2020/07/13 16:16"
        placement="right"
        preference="absolute"
        locale="en_US"
    >13/07/2020 16:16</tlp-relative-date>
</div>

<div class="tlp-property">
    <label class="tlp-label">Date in brazilian portuguese (relative, tooltip)</label>
    <tlp-relative-date
        date="2020-07-13T14:16:04.467Z"
        absolute-date="2020/07/13 16:16"
        placement="tooltip"
        preference="relative"
        locale="pt_BR"
    >13/07/2020 16:16</tlp-relative-date>
</div>

<div class="tlp-property">
    <label class="tlp-label">Date in brazilian portuguese (absolute, tooltip)</label>
    <tlp-relative-date
        date="2020-07-13T14:16:04.467Z"
        absolute-date="2020/07/13 16:16"
        placement="tooltip"
        preference="absolute"
        locale="pt_BR"
    >13/07/2020 16:16</tlp-relative-date>
</div>`
}

function getDatesInTable(): TemplateResult {
    // prettier-ignore
    return html`
<table class="tlp-table">
    <thead>
        <tr>
            <th>Text</th>
            <th>Date inside a table</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Absolute date</td>
            <td>
                <tlp-relative-date
                    date="2020-07-13T14:16:04.467Z"
                    absolute-date="2020/07/13 16:16"
                    placement="top"
                    preference="absolute"
                    locale="en_US"
                >13/07/2020 16:16</tlp-relative-date>
            </td>
        </tr>
        <tr>
            <td>Relative date</td>
            <td>
                <tlp-relative-date
                    date="2020-07-13T14:16:04.467Z"
                    absolute-date="2020/07/13 16:16"
                    placement="top"
                    preference="relative"
                    locale="en_US"
                >13/07/2020 16:16</tlp-relative-date>
            </td>
        </tr>
    </tbody>
</table>`
}

const meta: Meta<RelativeDatesProps> = {
    title: "TLP/Visual assets/Relative dates",
    parameters: {
        controls: {
            exclude: ["element", "table"],
        },
    },
    render: (args) => {
        return args.table ? getDatesInTable() : getDates();
    },
};

export default meta;
type Story = StoryObj<RelativeDatesProps>;

export const RelativeDates: Story = {};

export const RelativeDatesInTable: Story = {
    args: { table: true },
};
