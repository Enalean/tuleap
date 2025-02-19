/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { ToolbarBus, Heading } from "@tuleap/prose-mirror-editor";
import type { StyleElements } from "../../toolbar-element";
import { renderHeadingsOptions } from "./heading-option-template";
import { renderPlainTextOption } from "./plain-text-option-template";
import { renderStylesOption } from "./styles-option-template";
import { renderPreformattedTextOption } from "./preformatted-text-option-template";
import type { GetText } from "@tuleap/gettext";
import { applyTextStyle } from "./apply-text-style";

export const TAG = "text-style-item";

export type TextStyleItem = {
    toolbar_bus: ToolbarBus;
    style_elements: StyleElements;
    gettext_provider: GetText;
};

export type InternalTextStyleItem = Readonly<TextStyleItem> & {
    current_heading: Heading | null;
    is_plain_text_activated: boolean;
    is_preformatted_text_activated: boolean;
    is_disabled: boolean;
    select_element: HTMLSelectElement;
    render(): HTMLElement;
};

export type HostElement = InternalTextStyleItem & HTMLElement;

export const connect = (host: InternalTextStyleItem): void => {
    if (host.style_elements.headings) {
        host.toolbar_bus.setView({
            activateHeading: (heading: Heading | null) => {
                host.current_heading = heading;
            },
        });
    }

    if (host.style_elements.text) {
        host.toolbar_bus.setView({
            activatePlainText: (is_activated: boolean) => {
                host.is_plain_text_activated = is_activated;
            },
        });
    }

    if (host.style_elements.preformatted) {
        host.toolbar_bus.setView({
            activatePreformattedText: (is_activated: boolean) => {
                host.is_preformatted_text_activated = is_activated;
            },
        });
    }
};

const onChangeApplySelectedStyle = (host: InternalTextStyleItem): void => {
    applyTextStyle(host, host.select_element.value);
};

const getClasses = (host: InternalTextStyleItem): string => {
    return host.is_disabled
        ? "tlp-select tlp-select-small tlp-select-adjusted prose-mirror-toolbar-select-disabled"
        : "tlp-select tlp-select-small tlp-select-adjusted";
};

define<InternalTextStyleItem>({
    tag: TAG,
    current_heading: null,
    is_plain_text_activated: false,
    is_preformatted_text_activated: false,
    is_disabled: false,
    style_elements: (host, style_elements) => style_elements,
    toolbar_bus: {
        value: (host: InternalTextStyleItem, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    select_element: (host: InternalTextStyleItem) => {
        const select = host.render().querySelector("select");
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error("Unable to find the text-style <select> element");
        }
        return select;
    },
    render: (host: InternalTextStyleItem): UpdateFunction<InternalTextStyleItem> => html`
        <select
            class=${getClasses(host)}
            disabled="${host.is_disabled}"
            onchange="${onChangeApplySelectedStyle}"
        >
            ${renderStylesOption(host, host.gettext_provider)}
            ${renderPlainTextOption(host, host.gettext_provider)}
            ${renderHeadingsOptions(host, host.gettext_provider)}
            ${renderPreformattedTextOption(host, host.gettext_provider)}
        </select>
    `,
});
