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
import "./buttons/italic";
import "./buttons/subscript";
import "./buttons/superscript";
import "./buttons/unlink";
import "./buttons/link/link";
import "./buttons/image/image";
import "./buttons/ordered-list";
import "./buttons/bullet-list";

export type ProseMirrorToolbarElement = {
    controller: ControlToolbar;
};

export type TextElements = {
    bold: boolean;
    italic: boolean;
    code: boolean;
    quote: boolean;
};

export type ListElements = {
    ordered_list: boolean;
    bullet_list: boolean;
};

export type ScriptElements = {
    subscript: boolean;
    superscript: boolean;
};

export type LinkElements = {
    link: boolean;
    unlink: boolean;
    image: true;
};

export type InternalProseMirrorToolbarElement = Readonly<ProseMirrorToolbarElement> & {
    text_elements: TextElements | null;
    list_elements: ListElements | null;
    script_elements: ScriptElements | null;
    link_elements: LinkElements | null;
};

const TOOLBAR_TAG_NAME = "tuleap-prose-mirror-toolbar";

export const renderToolbar = (
    host: InternalProseMirrorToolbarElement,
): UpdateFunction<InternalProseMirrorToolbarElement> => {
    const bold_item = host.text_elements?.bold
        ? html`<bold-item toolbar_bus="${host.controller.getToolbarBus()}"></bold-item>`
        : html``;

    const italic_item = host.text_elements?.italic
        ? html`<italic-item toolbar_bus="${host.controller.getToolbarBus()}"></italic-item>`
        : html``;

    const code = host.text_elements?.code;
    const code_item = code
        ? html`<code-item toolbar_bus="${host.controller.getToolbarBus()}"></code-item>`
        : html``;

    const quote = host.text_elements?.quote;
    const quote_item = quote
        ? html`<quote-item toolbar_bus="${host.controller.getToolbarBus()}"></quote-item>`
        : html``;

    const ordered = host.list_elements?.ordered_list;
    const ordered_item = ordered
        ? html`<ordered-list-item
              toolbar_bus="${host.controller.getToolbarBus()}"
          ></ordered-list-item>`
        : html``;

    const bullet = host.list_elements?.bullet_list;
    const bullet_item = bullet
        ? html`<bullet-list-item
              toolbar_bus="${host.controller.getToolbarBus()}"
          ></bullet-list-item>`
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

    const link_item = host.link_elements?.link
        ? html`<link-item toolbar_bus="${host.controller.getToolbarBus()}"></link-item>`
        : html``;

    const unlink_item = host.link_elements?.unlink
        ? html`<unlink-item toolbar_bus="${host.controller.getToolbarBus()}"></unlink-item>`
        : html``;

    const image_item = host.link_elements?.image
        ? html`<image-item toolbar_bus="${host.controller.getToolbarBus()}"></image-item>`
        : html``;

    return html`
        <div class="prose-mirror-toolbar-container" data-test="toolbar-container">
            ${bold_item} ${italic_item} ${quote_item} ${code_item}
            <hr class="prose-mirror-hr" />
            ${link_item} ${unlink_item} ${image_item}
            <hr class="prose-mirror-hr" />
            ${bullet_item} ${ordered_item}
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
    link_elements: null,
    list_elements: null,
    render: {
        value: renderToolbar,
        shadow: false,
    },
});
