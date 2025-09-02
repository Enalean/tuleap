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
<div class="tlp-property">
    <label class="tlp-label">Simple value</label>
    <p>Muted, I currently have 4 windows open up… and I don’t know why.</p>
</div>

<div class="tlp-property">
    <label class="tlp-label">Paragraph value</label>
    <p>Far, far away, behind the word mountains,
        far from the <a href="https://example.com">countries Vokalia and Consonantia</a>,
        there live the blind texts. Separated they live
        in Bookmarksgrove right at the coast of the
        Semantics, a large language ocean. A small river
        named Duden flows by their place and supplies it
        with the necessary regelialia. It is a
        paradisematic country, in which roasted parts of
        sentences fly into your mouth. Even the
        all-powerful Pointing has no control about the
        blind texts it is an almost unorthographic life
        One day however a small line of blind text by
        the name of Lorem Ipsum decided to leave for the
        far World of Grammar. The Big Oxmox advised her
        not to do so, because there were thousands of
        bad Commas, …
    </p>
</div>

<div class="tlp-property">
    <label class="tlp-label">Blockquote value</label>
    <blockquote>Far, far away, behind the word mountains,
        far from the countries Vokalia and Consonantia,
        there live the blind texts. Separated they live
        in Bookmarksgrove right at the coast of the
        Semantics, a large language ocean.
    </blockquote>
</div>

<div class="tlp-property">
    <label class="tlp-label">Badge value</label>
    <span class="tlp-badge-primary">Badge</span>
</div>

<div class="tlp-property">
    <label class="tlp-label">Property with empty value</label>
    <span class="tlp-property-empty">Empty</span>
</div>`;
}

const meta: Meta = {
    title: "TLP/Forms/Properties",
    render: () => {
        return getTemplate();
    },
};

export default meta;
type Story = StoryObj;

export const Properties: Story = {};
