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
import type { ToolbarController } from "./ToolbarController";
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
import "./buttons/text-style/text-style";
import type { GetText } from "@tuleap/gettext";
import {
    getLocaleWithDefault,
    getPOFileFromLocaleWithoutExtension,
    initGettext,
} from "@tuleap/gettext";
import { buildToolbarItems } from "../helpers/build-toolbar-items-collection";

export type ItemGroupName =
    | "basic_text_items"
    | "list_items"
    | "link_items"
    | "scripts_items"
    | "text_styles_items"
    | "additional_items";

export const BASIC_TEXT_ITEMS_GROUP: ItemGroupName = "basic_text_items";
export const LIST_ITEMS_GROUP: ItemGroupName = "list_items";
export const LINK_ITEMS_GROUP: ItemGroupName = "link_items";
export const SCRIPTS_ITEMS_GROUP: ItemGroupName = "scripts_items";
export const TEXT_STYLES_ITEMS_GROUP: ItemGroupName = "text_styles_items";
export const ADDITIONAL_ITEMS_GROUP: ItemGroupName = "additional_items";

export type ItemGroup = {
    name: ItemGroupName;
    element: unknown;
};

export type ProseMirrorToolbarElement = {
    controller: ToolbarController;
    text_elements: TextElements | null;
    list_elements: ListElements | null;
    script_elements: ScriptElements | null;
    link_elements: LinkElements | null;
    style_elements: StyleElements | null;
    additional_elements: AdditionalElement[] | null;
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
    image: boolean;
};

export type StyleElements = {
    headings: boolean;
    text: boolean;
    preformatted: boolean;
};

export type AdditionalElement = {
    position: "before" | "after";
    target_name: ItemGroupName;
    item_element: HTMLElement;
};

export type InternalProseMirrorToolbarElement = Readonly<ProseMirrorToolbarElement> & {
    is_disabled: boolean;
};

export const TOOLBAR_TAG_NAME = "tuleap-prose-mirror-toolbar";

export const renderToolbar = (
    host: InternalProseMirrorToolbarElement,
    gettext_provider: GetText,
): UpdateFunction<InternalProseMirrorToolbarElement> => {
    const bold_item = host.text_elements?.bold
        ? html`<bold-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
          ></bold-item>`
        : html``;

    const italic_item = host.text_elements?.italic
        ? html`<italic-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
          ></italic-item>`
        : html``;

    const code = host.text_elements?.code;
    const code_item = code
        ? html`<code-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
          ></code-item>`
        : html``;

    const quote = host.text_elements?.quote;
    const quote_item = quote
        ? html`<quote-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
          ></quote-item>`
        : html``;

    const has_at_least_one_basic_text_element =
        host.text_elements?.bold ||
        host.text_elements?.italic ||
        host.text_elements?.code ||
        host.text_elements?.quote;
    const basic_text_items: ItemGroup = {
        name: BASIC_TEXT_ITEMS_GROUP,
        element: has_at_least_one_basic_text_element
            ? html`<span class="prose-mirror-button-group">
                  ${bold_item} ${italic_item} ${quote_item} ${code_item}
              </span>`
            : html``,
    };

    const ordered = host.list_elements?.ordered_list;
    const ordered_item = ordered
        ? html`<ordered-list-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
              is_toolbar_disabled="${host.is_disabled}"
          ></ordered-list-item>`
        : html``;

    const bullet = host.list_elements?.bullet_list;
    const bullet_item = bullet
        ? html`<bullet-list-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
              is_toolbar_disabled="${host.is_disabled}"
          ></bullet-list-item>`
        : html``;

    const list_items: ItemGroup = {
        name: LIST_ITEMS_GROUP,
        element:
            host.list_elements?.ordered_list || host.list_elements?.bullet_list
                ? html`<span class="prose-mirror-button-group"
                      >${bullet_item} ${ordered_item}</span
                  >`
                : html``,
    };
    const subscript = host.script_elements?.subscript;
    const subscript_item = subscript
        ? html`<subscript-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
          ></subscript-item>`
        : html``;

    const superscript = host.script_elements?.superscript;
    const superscript_item = superscript
        ? html`<superscript-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
          ></superscript-item>`
        : html``;

    const has_supersubscript_elements =
        host.script_elements?.subscript || host.script_elements?.superscript;
    const supersubscript_items: ItemGroup = {
        name: SCRIPTS_ITEMS_GROUP,
        element: has_supersubscript_elements
            ? html`<span class="prose-mirror-button-group">
                  ${subscript_item} ${superscript_item}
              </span>`
            : html``,
    };

    const link_item = host.link_elements?.link
        ? html`<link-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
              is_toolbar_disabled="${host.is_disabled}"
          ></link-item>`
        : html``;

    const unlink_item = host.link_elements?.unlink
        ? html`<unlink-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
          ></unlink-item>`
        : html``;

    const image_item = host.link_elements?.image
        ? html`<image-item
              toolbar_bus="${host.controller.getToolbarBus()}"
              gettext_provider="${gettext_provider}"
              is_disabled="${host.is_disabled}"
          ></image-item>`
        : html``;

    const has_at_least_one_link_element =
        host.link_elements?.link || host.link_elements?.unlink || host.link_elements?.image;
    const link_items: ItemGroup = {
        name: LINK_ITEMS_GROUP,
        element: has_at_least_one_link_element
            ? html`<span class="prose-mirror-button-group">
                  ${link_item} ${unlink_item} ${image_item}
              </span>`
            : html``,
    };

    const has_at_least_one_style_element_activated =
        host.style_elements !== null &&
        (host.style_elements.headings ||
            host.style_elements.text ||
            host.style_elements.preformatted);
    const text_style_item: ItemGroup = {
        name: TEXT_STYLES_ITEMS_GROUP,
        element: has_at_least_one_style_element_activated
            ? html` <span class="prose-mirror-button-group">
                  <text-style-item
                      toolbar_bus="${host.controller.getToolbarBus()}"
                      style_elements="${host.style_elements}"
                      gettext_provider="${gettext_provider}"
                      is_disabled="${host.is_disabled}"
                  ></text-style-item>
              </span>`
            : html``,
    };

    const default_item_groups_collection: ItemGroup[] = [
        basic_text_items,
        text_style_item,
        list_items,
        link_items,
        supersubscript_items,
    ];

    return html`
        <div class="prose-mirror-toolbar-container" data-test="toolbar-container">
            ${buildToolbarItems(
                default_item_groups_collection,
                host.additional_elements ? host.additional_elements : [],
            ).map((items) => html`${items.element}`)}
        </div>
    `.style(scss_styles);
};

initGettext(
    getLocaleWithDefault(document),
    "tlp-prose-mirror-toolbar",
    (locale) => import(`../../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
).then((gettext_provider) => {
    define<InternalProseMirrorToolbarElement>({
        tag: TOOLBAR_TAG_NAME,
        controller: (host, controller) => controller,
        text_elements: null,
        script_elements: null,
        link_elements: null,
        list_elements: null,
        style_elements: null,
        additional_elements: null,
        is_disabled: {
            value: true,
            connect: (host) => {
                host.controller.getToolbarBus().setView({
                    toggleToolbarState: (is_enabled: boolean): void => {
                        host.is_disabled = !is_enabled;
                    },
                });
            },
        },
        render: {
            value: (host: InternalProseMirrorToolbarElement) =>
                renderToolbar(host, gettext_provider),
            shadow: false,
        },
    });
});
