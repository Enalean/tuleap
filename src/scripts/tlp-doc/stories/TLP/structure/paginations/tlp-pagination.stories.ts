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

type PaginationProps = {
    offset: number;
    limit: number;
    total: number;
};

function getTemplate(args: PaginationProps): TemplateResult {
    const page_end_index = Math.min(args.offset + args.limit, args.total);
    // prettier-ignore
    return html`
<div class="tlp-pagination">
    <button
        type="button"
        class="tlp-button-primary tlp-button-outline tlp-pagination-button"
        title="First"
        ?disabled="${args.offset <= 0}"
    >
        <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
    </button>
    <button
        type="button"
        class="tlp-button-primary tlp-button-outline tlp-pagination-button"
        title="Previous"
        ?disabled="${args.offset <= 0}"
    >
        <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
    </button>
    <span class="tlp-pagination-pages">
        <span class="tlp-pagination-number">${args.offset}</span>
        –
        <span class="tlp-pagination-number">${page_end_index}</span>
        of
        <span class="tlp-pagination-number">${args.total}</span>
    </span>
    <button
        type="button"
        class="tlp-button-primary tlp-button-outline tlp-pagination-button"
        title="Next"
        ?disabled="${args.offset + args.limit >= args.total}"
    >
        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
    </button>
    <button
        type="button"
        class="tlp-button-primary tlp-button-outline tlp-pagination-button"
        title="Last"
        ?disabled="${args.offset + args.limit >= args.total}"
    >
        <i class="fa-solid fa-angles-right" aria-hidden="true"></i>
    </button>
</div>`
}

const meta: Meta<PaginationProps> = {
    title: "TLP/Structure & Navigation/Pagination",
    render: (args) => getTemplate(args),
    args: {
        offset: 31,
        limit: 30,
        total: 79,
    },
    argTypes: {
        offset: {
            name: "Offset",
            description: "The starting index of the current page",
        },
        limit: {
            name: "Limit",
            description: "The number of items per page",
        },
        total: {
            name: "Total",
            description: "The total number of items",
        },
    },
};
export default meta;
type Story = StoryObj;

export const Pagination: Story = {};
