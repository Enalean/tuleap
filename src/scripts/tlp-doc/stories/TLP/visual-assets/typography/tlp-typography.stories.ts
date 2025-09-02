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

type TypographyProps = {
    element: string;
};

function getHeadings(): TemplateResult {
    // prettier-ignore
    return html`
<h1>H1. Heading</h1>
<h2>H2. Heading</h2>
<h3>H3. Heading</h3>
<h4>H4. Heading</h4>
<h5>H5. Heading</h5>
<h6>H6. Heading</h6>
<hr>
<h1>H1. Heading <small>Secondary text</small></h1>
<h2>H2. Heading <small>Secondary text</small></h2>
<h3>H3. Heading <small>Secondary text</small></h3>
<h4>H4. Heading <small>Secondary text</small></h4>
<h5>H5. Heading <small>Secondary text</small></h5>
<h6>H6. Heading <small>Secondary text</small></h6>`;
}

function getParagraph(): TemplateResult {
    // prettier-ignore
    return html`
<p>
    Far far away, behind the word mountains, far from the countries <sub>Vokalia</sub> and
    <sup>Consonantia</sup>, there live the blind texts. Separated they live in
    Bookmarksgrove right at the coast of the Semantics, a large language ocean. A small
    river named Duden flows by their place and supplies it with the necessary regelialia. It
    is a paradisematic country, in which roasted parts of sentences fly into your mouth.
    Even the all-powerful Pointing has no control about the blind texts it is an almost
    unorthographic life One day however a small line of blind text by the name of Lorem
    Ipsum decided to leave for the far World of Grammar. The Big Oxmox advised her not to do
    so, because there were thousands of bad Commas, …
</p>

<p>
    <a href="https://example.com">Normal link</a>
</p>

<blockquote>
    Far far away, behind the word mountains, far from the countries Vokalia and Consonantia,
    there live the blind texts. Separated they live in Bookmarksgrove right at the coast of
    the Semantics, a large language ocean.
</blockquote>

<p class="tlp-text-muted">
    Muted, I currently have 4 windows open up… and I don’t know why.
</p>
<p class="tlp-text-info">
    Info, I currently have 4 windows open up… and I don’t know why.
</p>
<p class="tlp-text-success">
    Success, I currently have 4 windows open up… and I don’t know why.
</p>
<p class="tlp-text-warning">
    Warning, I currently have 4 windows open up… and I don’t know why.
</p>
<p class="tlp-text-danger">
    Danger, I currently have 4 windows open up… and I don’t know why.
</p>

<p>Please press <kbd>⌘ Cmd</kbd> + <kbd>S</kbd> to save the page.</p>

<p>Check out my inline code: <code>var tlp_code = this.is_my[tlp-code];</code></p>

<pre><code>colors.forEach(function (color) {
    gulp.task('sass:compress-' + color, function() {
        return compressForAGivenColor(color);
    });
});</code></pre>

<ul>
    <li>A list</li>
    <li>
        with many
        <ul>
            <li>and probably</li>
            <li>nested</li>
        </ul>
    </li>
    <li>bullets</li>
</ul>

<ol>
    <li>A numbered list</li>
    <li>
        with many
        <ol>
            <li>and probably</li>
            <li>nested</li>
        </ol>
    </li>
    <li>bullets</li>
</ol>`;
}

const meta: Meta<TypographyProps> = {
    title: "TLP/Visual assets/Typography",
    parameters: {
        controls: {
            exclude: ["element"],
        },
    },
    render: (args) => {
        switch (args.element) {
            default:
                return html``;
            case "title":
                return getHeadings();
            case "paragraph":
                return getParagraph();
        }
    },
};

export default meta;
type Story = StoryObj<TypographyProps>;

export const Title: Story = {
    args: { element: "title" },
};

export const Paragraph: Story = {
    args: { element: "paragraph" },
};
