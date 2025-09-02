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
import type { TemplateResult } from "lit";
import { html } from "lit";
import "./TableWrapper";

type TableProps = {
    empty_state: boolean;
    with_actions: boolean;
    filterable: boolean;
};

function getFilterableTemplate(): TemplateResult {
    //prettier-ignore
    return html`
<div class="tlp-table-actions">
    <button type="button" class="tlp-button-primary tlp-table-actions-element"><i class="tlp-button-icon fa-solid fa-plus" aria-hidden="true"></i> Add user</button>
    <div class="tlp-table-actions-spacer"></div>
    <div class="tlp-form-element tlp-table-actions-element">
        <input type="search"
               class="tlp-search tlp-table-actions-filter"
               id="filter-table"
               data-target-table-id="my-table"
               autocomplete="off"
               placeholder="Firstname or age">
    </div>
</div>
<table class="tlp-table" id="my-table">
    <thead>
        <tr>
            <th class="tlp-table-cell-numeric">
                <a href="https://example.com">Id</a>
            </th>
            <th class="tlp-table-cell-filterable">Firstname</th>
            <th>Lastname</th>
            <th class="tlp-table-cell-numeric">Age</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <tr class="tlp-table-empty-filter">
            <td colspan="5" class="tlp-table-cell-empty">
                There isn't any matching users
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">1</a>
            </td>
            <td class="tlp-table-cell-filterable">Allen</td>
            <td>Woody</td>
            <td class="tlp-table-cell-numeric tlp-table-cell-filterable">34</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">8</a>
            </td>
            <td class="tlp-table-cell-filterable">Brian</td>
            <td>Wilson</td>
            <td class="tlp-table-cell-numeric tlp-table-cell-filterable">49</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">149</a>
            </td>
            <td class="tlp-table-cell-filterable">John</td>
            <td>Bonham</td>
            <td class="tlp-table-cell-numeric tlp-table-cell-filterable">28</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">54</a>
            </td>
            <td class="tlp-table-cell-filterable">June</td>
            <td>Carter</td>
            <td class="tlp-table-cell-numeric tlp-table-cell-filterable">12</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td class="tlp-table-cell-section tlp-table-cell-filterable" colspan="5">Podium</td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">55</a>
            </td>
            <td class="tlp-table-cell-filterable">Johnny</td>
            <td>Cash</td>
            <td class="tlp-table-cell-numeric tlp-table-cell-filterable">29</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">3</a>
            </td>
            <td class="tlp-table-cell-filterable">Mitch</td>
            <td>Mitchell</td>
            <td class="tlp-table-cell-numeric tlp-table-cell-filterable">89</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">43</a>
            </td>
            <td class="tlp-table-cell-filterable">John Paul</td>
            <td>Jones</td>
            <td class="tlp-table-cell-numeric tlp-table-cell-filterable">34</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">387</a>
            </td>
            <td class="tlp-table-cell-filterable">John</td>
            <td>Bronze</td>
            <td class="tlp-table-cell-numeric tlp-table-cell-filterable">22</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
            </td>
        </tr>
    </tbody>
</table>`;
}

function getTemplate(args: TableProps): TemplateResult {
    //prettier-ignore
    return html`${args.with_actions ? html`
<div class="tlp-table-actions">
    <button type="button" class="tlp-button-primary tlp-table-actions-element"><i class="tlp-button-icon fa-solid fa-plus" aria-hidden="true"></i> Add user</button>
    <button type="button" class="tlp-button-primary tlp-button-outline tlp-table-actions-element"><i class="tlp-button-icon fa-solid fa-download" aria-hidden="true"></i> Export CSV</button>
</div>` : html ``}
<table class="tlp-table">
    <thead>
        <tr>
            <th class="tlp-table-cell-numeric">
                <a href="https://example.com">Id</a>
            </th>
            <th>Firstname</th>
            <th>Lastname</th>
            <th><a href="https://example.com" class="tlp-table-sort">
                Status <i class="fa-solid fa-caret-down tlp-table-sort-icon" aria-hidden="true"></i>
            </th>
            <th class="tlp-table-cell-numeric">Age</th>
            <th class="tlp-table-cell-numeric">Price</th>
            <th></th>
        </tr>
    </thead>${args.empty_state ? html`
    <tbody>
    <tr>
        <td colspan="7" class="tlp-table-cell-empty">
            There isn't any matching users
        </td>
    </tr>
    </tbody>` : html`
    <tbody>
        <tr>
            <td class="tlp-table-cell-section" colspan="7">Podium</td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">1</a>
            </td>
            <td>Allen</td>
            <td>Woody</td>
            <td>
                <span class="tlp-badge-success tlp-badge-outline">Active</span>
            </td>
            <td class="tlp-table-cell-numeric">34</td>
            <td class="tlp-table-cell-numeric">$950.50</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-regular fa-trash-can" aria-hidden="true"></i> Remove
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">8</a>
            </td>
            <td>Brian</td>
            <td>Wilson</td>
            <td>
                <span class="tlp-badge-danger tlp-badge-outline">Disabled</span>
            </td>
            <td class="tlp-table-cell-numeric">49</td>
            <td class="tlp-table-cell-numeric">$12</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-regular fa-trash-can" aria-hidden="true"></i> Remove
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">149</a>
            </td>
            <td>John</td>
            <td>Bonham</td>
            <td>
                <span class="tlp-badge-warning">
                    <i class="tlp-badge-icon fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Unknown
                </span>
            </td>
            <td class="tlp-table-cell-numeric">28</td>
            <td class="tlp-table-cell-numeric">$1 250</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-regular fa-trash-can" aria-hidden="true"></i> Remove
                </a>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td class="tlp-table-cell-section" colspan="7">Others</td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">54</a>
            </td>
            <td>June</td>
            <td>Carter</td>
            <td>
                <span class="tlp-badge-success tlp-badge-outline">Active</span>
            </td>
            <td class="tlp-table-cell-numeric">12</td>
            <td class="tlp-table-cell-numeric">$98</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-regular fa-trash-can" aria-hidden="true"></i> Remove
                </a>
            </td>
        </tr>
        <tr class="tlp-table-row-success">
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">55</a>
            </td>
            <td>Johnny</td>
            <td>Cash</td>
            <td>
                <span class="tlp-badge-success tlp-badge-outline">Active</span>
            </td>
            <td class="tlp-table-cell-numeric">29</td>
            <td class="tlp-table-cell-numeric">$104.45</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-regular fa-trash-can" aria-hidden="true"></i> Remove
                </a>
            </td>
        </tr>
        <tr class="tlp-table-row-success">
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">3</a>
            </td>
            <td>Mitch</td>
            <td>Mitchell</td>
            <td>
                <span class="tlp-badge-success tlp-badge-outline">Active</span>
            </td>
            <td class="tlp-table-cell-numeric">89</td>
            <td class="tlp-table-cell-numeric">$250</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-regular fa-trash-can" aria-hidden="true"></i> Remove
                </a>
            </td>
        </tr>
        <tr class="tlp-table-row-danger">
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">43</a>
            </td>
            <td>John Paul</td>
            <td>Jones</td>
            <td>
                <span class="tlp-badge-danger tlp-badge-outline">Disabled</span>
            </td>
            <td class="tlp-table-cell-numeric">34</td>
            <td class="tlp-table-cell-numeric">$847.90</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-regular fa-trash-can" aria-hidden="true"></i> Remove
                </a>
            </td>
        </tr>
        <tr>
            <td class="tlp-table-cell-numeric">
                <a href="https://example.com">387</a>
            </td>
            <td>John</td>
            <td>Bronze</td>
            <td>
                <span class="tlp-badge-success tlp-badge-outline">Active</span>
            </td>
            <td class="tlp-table-cell-numeric">22</td>
            <td class="tlp-table-cell-numeric">$3</td>
            <td class="tlp-table-cell-actions">
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-solid fa-hand-peace" aria-hidden="true"></i> Peace
                </a>
                <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="https://example.com">
                    <i class="tlp-button-icon fa-regular fa-trash-can" aria-hidden="true"></i> Remove
                </a>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th class="tlp-table-cell-numeric">Avg: 37</th>
            <th class="tlp-table-cell-numeric">Sum: $3515.85</th>
            <th></th>
        </tr>
    </tfoot>
</table>`}`;
}

const meta: Meta<TableProps> = {
    title: "TLP/Tables/Tables",
    parameters: {
        controls: {
            exclude: ["filterable"],
        },
    },
    render: (args) => {
        if (args.filterable) {
            return getFilterableTemplate();
        }
        return getTemplate(args);
    },
};

export default meta;
type Story = StoryObj<TableProps>;

export const Simple: Story = {
    args: {
        empty_state: false,
        with_actions: false,
        filterable: false,
    },
    argTypes: {
        empty_state: {
            name: "Empty state",
            description:
                "Empty result sets should be displayed in a table with a unique cell that echoes a user friendly message. Please adapt the colspan attribute to your usage and add the class",
            table: {
                type: { summary: ".tlp-table-cell-empty" },
            },
        },
        with_actions: {
            name: "With actions",
            description: "Action buttons must have the class",
            table: {
                type: { summary: ".tlp-table-actions" },
            },
        },
    },
};

export const InlineFilters: Story = {
    args: {
        filterable: true,
    },
    decorators: [
        (story): TemplateResult => html`<tuleap-table-wrapper>${story()}</tuleap-table-wrapper>`,
    ],
};
