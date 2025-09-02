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

function getTemplate(): TemplateResult {
    // prettier-ignore
    return html`
<i class="fa-solid fa-house" aria-hidden="true"></i>
<i class="fa-solid fa-inbox" aria-hidden="true"></i>
<i class="fa-solid fa-globe" aria-hidden="true"></i>
<i class="fa-solid fa-plus" aria-hidden="true"></i>
<i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i>
<i class="fa-solid fa-xmark" aria-hidden="true"></i>
<i class="fa-solid fa-table-list" aria-hidden="true"></i>
<i class="fa-solid fa-user" aria-hidden="true"></i>
<i class="fa-solid fa-users" aria-hidden="true"></i>
<i class="fa-solid fa-bars" aria-hidden="true"></i>
<i class="fa-solid fa-arrow-down-short-wide" aria-hidden="true"></i>
<i class="fa-solid fa-unlock" aria-hidden="true"></i>
<i class="fa-solid fa-tags" aria-hidden="true"></i>
`
}

const meta: Meta = {
    title: "TLP/Visual assets/Iconography",
    render: () => {
        return getTemplate();
    },
};
export default meta;
type Story = StoryObj;

export const FontAwesomeIcons: Story = {};
