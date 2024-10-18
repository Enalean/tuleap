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
import { NB_HEADING } from "@tuleap/prose-mirror-editor";
import type { InternalHeadingsItem } from "./text-style";
import type { GetText } from "@tuleap/gettext";

const isCurrentHeading = (host: InternalHeadingsItem, level: number): boolean => {
    return host.current_heading !== null && host.current_heading.level === level;
};

const onClickApplyHeading = (host: InternalHeadingsItem, level: number): void => {
    if (isCurrentHeading(host, level)) {
        return;
    }

    host.toolbar_bus.heading({ level });
};

const getHeadingOptionTitle = (level: number, gettext_provider: GetText): string =>
    sprintf(
        gettext_provider.gettext(
            "Change to heading %(heading_level)s `Ctrl+Shift+%(heading_level)s`",
        ),
        { heading_level: level },
    );

const getHeadingOptionTextContent = (level: number, gettext_provider: GetText): string =>
    sprintf(gettext_provider.gettext("Heading %(heading_level)s"), { heading_level: level });

export const renderHeadingOption = (
    host: InternalHeadingsItem,
    level: number,
    gettext_provider: GetText,
): UpdateFunction<InternalHeadingsItem> => html`
    <option
        onclick="${(): void => onClickApplyHeading(host, level)}"
        title="${getHeadingOptionTitle(level, gettext_provider)}"
        selected="${isCurrentHeading(host, level)}"
    >
        ${getHeadingOptionTextContent(level, gettext_provider)}
    </option>
`;

export const renderHeadingsOptions = (
    host: InternalHeadingsItem,
    gettext_provider: GetText,
): UpdateFunction<InternalHeadingsItem> => {
    if (!host.style_elements.headings) {
        return html``;
    }

    const heading_options = [];
    for (let level = 1; level < NB_HEADING + 1; level++) {
        heading_options.push(renderHeadingOption(host, level, gettext_provider));
    }
    return html`${heading_options}`;
};
