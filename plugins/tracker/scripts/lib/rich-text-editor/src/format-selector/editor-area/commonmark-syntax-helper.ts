/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import type { GettextProvider } from "@tuleap/gettext";
import type { TemplateResult } from "lit-html";
import { html, unsafeStatic } from "lit-html/static.js";

export function getCommonMarkSyntaxPopoverHelperContent(
    gettext_provider: GettextProvider,
): TemplateResult {
    return html`
        <template data-popover-content>
            <section data-popover-root>
                <div class="tlp-popover-header">
                    <h1 class="tlp-popover-title helper-popover-title">
                        ${unsafeStatic(gettext_provider.gettext("For your information..."))}
                    </h1>
                </div>
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th>${unsafeStatic(gettext_provider.gettext("Type..."))}</th>
                            <th>${unsafeStatic(gettext_provider.gettext("...to get"))}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>_italic_</td>
                            <td><em>italic</em></td>
                        </tr>
                        <tr>
                            <td>**bold**</td>
                            <td><b>bold</b></td>
                        </tr>
                        <tr>
                            <td># Heading 1</td>
                            <td><h1 class="popover-h1-indentation">Heading 1</h1></td>
                        </tr>
                        <tr>
                            <td>## Heading 2</td>
                            <td><h2 class="popover-h2-indentation">Heading 2</h2></td>
                        </tr>
                        <tr>
                            <td class="popover-link-indentation">[Link](https://example.com)</td>
                            <td><a href="https://example.com">Link</a></td>
                        </tr>
                        <tr>
                            <td>![Image](/path/image.png)</td>
                            <td>
                                <div class="popover-image-indentation"></div>
                            </td>
                        </tr>
                        <tr>
                            <td>> Blockquote</td>
                            <td>
                                <blockquote class="popover-blockquote-indentation">
                                    Blockquote
                                </blockquote>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                - Item 1
                                <br />
                                - Item 2
                                <br />
                            </td>
                            <td>
                                <ul>
                                    <li>Item 1</li>
                                    <li>Item 2</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1. Item 1
                                <br />
                                2. Item 2
                                <br />
                            </td>
                            <td>
                                <ol>
                                    <li>Item 1</li>
                                    <li>Item 2</li>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <td>\`Inline code\`</td>
                            <td><code>Inline code</code></td>
                        </tr>
                        <tr>
                            <td>
                                \`\`\`
                                <br />
                                a = 'Hello ';
                                <br />
                                b = 'World';
                                <br />
                                echo a.b;
                                <br />
                                # display Hello World
                                <br />
                                \`\`\`
                            </td>
                            <td>
                                <pre><code>a = 'Hello';
b = 'World';
echo a.b;
#display Hello World
</code></pre>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </template>
    `;
}
