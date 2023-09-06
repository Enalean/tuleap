/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { GettextProvider } from "@tuleap/gettext";
import type { InternalCommonmarkPopover } from "./CommonmarkPopover";
import popover_styles from "../themes/style.scss";

export const POPOVER_TRIGGER_CLASSNAME = "commonmark-popover-trigger";
export const POPOVER_CLASSNAME = "commonmark-popover-content";
export const POPOVER_ANCHOR_CLASSNAME = "commonmark-popover-trigger-icon";

export const getPopoverTemplate = (
    host: InternalCommonmarkPopover,
    gettext_provider: GettextProvider,
): UpdateFunction<InternalCommonmarkPopover> => {
    const onClickIgnore = (host: InternalCommonmarkPopover, event: MouseEvent): void => {
        event.preventDefault();
        event.stopImmediatePropagation();
    };

    const popover_trigger_label_classes = {
        "commonmark-popover-trigger-label": true,
        "common-mark-popover-trigger-active": host.is_open,
    };

    const popover_trigger_icon_classes = {
        "fa-solid": true,
        "fa-circle-question": true,
        "fa-fw": true,
        [POPOVER_ANCHOR_CLASSNAME]: true,
        "common-mark-popover-trigger-active": host.is_open,
    };

    return html`
        <div class="${POPOVER_TRIGGER_CLASSNAME}">
            <span class="${popover_trigger_label_classes}" data-test="popover-trigger-label">
                ${gettext_provider.gettext("Markdown supported")}
            </span>
            <i class="${popover_trigger_icon_classes}" data-test="popover-trigger-icon"></i>
        </div>
        <section
            class="tlp-popover tlp-popover-dark ${POPOVER_CLASSNAME}"
            ontlp-popover-shown="${host.controller.onPopoverShown}"
            ontlp-popover-hidden="${host.controller.onPopoverHidden}"
            data-test="popover-content"
        >
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header commonmark-popover-header">
                <div class="tlp-popover-title commonmark-popover-title">
                    <div>${gettext_provider.gettext("Write this...")}</div>
                    <div>${gettext_provider.gettext("...to see")}</div>
                </div>
            </div>
            <div class="tlp-popover-body commonmark-popover-body">
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">_italic_</div>
                    <div class="commonmark-popover-example-column"><em>italic</em></div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">**bold**</div>
                    <div class="commonmark-popover-example-column"><b>bold</b></div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column"># Heading 1</div>
                    <div class="commonmark-popover-example-column">
                        <h1 class="commonmark-example-header">Heading 1</h1>
                    </div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">## Heading 2</div>
                    <div class="commonmark-popover-example-column">
                        <h2 class="commonmark-example-header">Heading 2</h2>
                    </div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">[Link](https://example.com)</div>
                    <div class="commonmark-popover-example-column">
                        <a href="#" class="commonmark-example-link" onclick="${onClickIgnore}"
                            >Link</a
                        >
                    </div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">![Image](/path/image.png)</div>
                    <div class="commonmark-popover-example-column">
                        <div class="commonmark-example-image"></div>
                    </div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">> Blockquote</div>
                    <div class="commonmark-popover-example-column">
                        <blockquote class="commonmark-example-blockquote">Blockquote</blockquote>
                    </div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">
                        - Item 1
                        <br />
                        - Item 2
                    </div>
                    <div class="commonmark-popover-example-column">
                        <ul class="commonmark-example-list">
                            <li>Item 1</li>
                            <li>Item 2</li>
                        </ul>
                    </div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">
                        1. Item 1
                        <br />
                        2. Item 2
                    </div>
                    <div class="commonmark-popover-example-column">
                        <ol class="commonmark-example-list">
                            <li>Item 1</li>
                            <li>Item 2</li>
                        </ol>
                    </div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">\`Inline code\`</div>
                    <div class="commonmark-popover-example-column">
                        <code>Inline code</code>
                    </div>
                </div>
                <div class="commonmark-popover-example-row">
                    <div class="commonmark-popover-example-column">
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
                    </div>
                    <div class="commonmark-popover-example-column commonmark-example-code-block">
                        <pre>
                            <code>
a = 'Hello';
b = 'World';
echo a.b;
#display Hello World
                            </code>
                        </pre>
                    </div>
                </div>
            </div>
        </section>
    `.style(popover_styles);
};
