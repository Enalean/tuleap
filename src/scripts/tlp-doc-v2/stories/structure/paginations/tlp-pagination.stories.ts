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

import type { Meta, StoryObj } from "@storybook/web-components";
import { html, type TemplateResult } from "lit";

function getTemplate(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-pagination">
    <a
        class="tlp-button-primary tlp-button-outline tlp-pagination-button"
        role="button"
        title="Begin"
    >
        <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
    </a>
    <a
        class="tlp-button-primary tlp-button-outline tlp-pagination-button"
        role="button"
        title="Previous"
    >
        <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
    </a>

    <span class="tlp-pagination-pages">
        <span class="tlp-pagination-number">51</span>
        –
        <span class="tlp-pagination-number">79</span>
        of
        <span class="tlp-pagination-number">79</span>
    </span>

    <a
        class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
        role="button"
        title="Next"
    >
        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
    </a>
    <a
        class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
        role="button"
        title="End"
    >
        <i class="fa-solid fa-angles-right" aria-hidden="true"></i>
    </a>
</div>`
}

const meta: Meta = {
    title: "TLP/Structure & Navigation/Pagination",
    render: () => {
        return getTemplate();
    },
};
export default meta;
type Story = StoryObj;

export const Pagination: Story = {};
