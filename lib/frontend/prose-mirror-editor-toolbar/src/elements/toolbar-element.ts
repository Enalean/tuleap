/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import type { UpdateFunction } from "hybrids";
import { define, html } from "hybrids";
import type { ControlToolbar } from "./ToolbarController";
import scss_styles from "./styles.scss?inline";
import "./buttons/bold";
import "./buttons/code";
import "./buttons/quote";
import "./buttons/embedded";
import "./buttons/subscript";
import "./buttons/superscript";

export type ProseMirrorToolbarElement = {
    controller: ControlToolbar;
};

export type TextElements = {
    bold: boolean;
    embedded: boolean;
    code: boolean;
    quote: boolean;
};

export type ScriptElements = {
    subscript: boolean;
    superscript: boolean;
};

export type InternalProseMirrorToolbarElement = Readonly<ProseMirrorToolbarElement> & {
    text_elements: TextElements | null;
    script_elements: ScriptElements | null;
};

const TOOLBAR_TAG_NAME = "tuleap-prose-mirror-toolbar";

export const renderToolbar = (
    host: InternalProseMirrorToolbarElement,
): UpdateFunction<InternalProseMirrorToolbarElement> => {
    const bold_item = host.text_elements?.bold
        ? html`<bold-item toolbar_bus="${host.controller.getToolbarBus()}"></bold-item>`
        : html``;

    const embedded_item = host.text_elements?.embedded
        ? html`<embedded-item toolbar_bus="${host.controller.getToolbarBus()}"></embedded-item>`
        : html``;

    const code = host.text_elements?.code;
    const code_item = code
        ? html`<code-item toolbar_bus="${host.controller.getToolbarBus()}"></code-item>`
        : html``;

    const quote = host.text_elements?.quote;
    const quote_item = quote
        ? html`<quote-item toolbar_bus="${host.controller.getToolbarBus()}"></quote-item>`
        : html``;

    const subscript = host.script_elements?.subscript;
    const subscript_item = subscript
        ? html`<subscript-item toolbar_bus="${host.controller.getToolbarBus()}"></subscript-item>`
        : html``;

    const superscript = host.script_elements?.superscript;
    const superscript_item = superscript
        ? html`<superscript-item
              toolbar_bus="${host.controller.getToolbarBus()}"
          ></superscript-item>`
        : html``;

    return html`
        <div class="prose-mirror-toolbar-container" data-test="toolbar-container">
            ${bold_item} ${embedded_item} ${code_item} ${quote_item}
            <hr class="prose-mirror-hr" />
            ${subscript_item} ${superscript_item}
            <hr class="prose-mirror-hr" />
        </div>
    `.style(scss_styles);
};

define<InternalProseMirrorToolbarElement>({
    tag: TOOLBAR_TAG_NAME,
    controller: (host, controller) => controller,
    text_elements: null,
    script_elements: null,
    render: {
        value: renderToolbar,
        shadow: false,
    },
});
