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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { sprintf } from "sprintf-js";
import type { InternalTextStyleItem } from "./text-style";
import type { GetText } from "@tuleap/gettext";

export const OPTION_HEADING_1 = "heading-1";
export const OPTION_HEADING_2 = "heading-2";
export const OPTION_HEADING_3 = "heading-3";

export const isCurrentHeading = (host: InternalTextStyleItem, level: number): boolean => {
    return host.current_heading !== null && host.current_heading.level === level;
};

const getHeadingOptionTitle = (level: number, gettext_provider: GetText): string => {
    switch (level) {
        case 1:
            return sprintf(
                gettext_provider.gettext("Change to large heading `Ctrl+Shift+%(heading_level)s`"),
                { heading_level: level },
            );
        case 2:
            return sprintf(
                gettext_provider.gettext("Change to medium heading `Ctrl+Shift+%(heading_level)s`"),
                { heading_level: level },
            );
        default:
            return sprintf(
                gettext_provider.gettext("Change to small heading `Ctrl+Shift+%(heading_level)s`"),
                { heading_level: level },
            );
    }
};

const getHeadingOptionTextContent = (level: number, gettext_provider: GetText): string => {
    switch (level) {
        case 1:
            return gettext_provider.gettext("Large heading");
        case 2:
            return gettext_provider.gettext("Medium heading");
        default:
            return gettext_provider.gettext("Small heading");
    }
};

export const renderHeadingOption = (
    host: InternalTextStyleItem,
    level: number,
    value: string,
    gettext_provider: GetText,
): UpdateFunction<InternalTextStyleItem> => html`
    <option
        title="${getHeadingOptionTitle(level, gettext_provider)}"
        selected="${isCurrentHeading(host, level)}"
        value="${value}"
    >
        ${getHeadingOptionTextContent(level, gettext_provider)}
    </option>
`;

export const renderHeadingsOptions = (
    host: InternalTextStyleItem,
    gettext_provider: GetText,
): UpdateFunction<InternalTextStyleItem> => {
    if (!host.style_elements.headings) {
        return html``;
    }

    const header_values = [
        { value: OPTION_HEADING_1, level: 1 },
        { value: OPTION_HEADING_2, level: 2 },
        { value: OPTION_HEADING_3, level: 3 },
    ];

    const heading_options = [];
    for (const option of header_values) {
        heading_options.push(
            renderHeadingOption(host, option.level, option.value, gettext_provider),
        );
    }
    return html`${heading_options}`;
};
