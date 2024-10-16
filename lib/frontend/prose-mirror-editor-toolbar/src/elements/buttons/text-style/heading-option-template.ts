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
import { gettext_provider } from "../../../gettext-provider";
import type { InternalHeadingsItem } from "./text-style";

const isCurrentHeading = (host: InternalHeadingsItem, level: number): boolean => {
    return host.current_heading !== null && host.current_heading.level === level;
};

const onClickApplyHeading = (host: InternalHeadingsItem, level: number): void => {
    if (isCurrentHeading(host, level)) {
        return;
    }

    host.toolbar_bus.heading({ level });
};

const getHeadingOptionTitle = (level: number): string =>
    sprintf(
        gettext_provider.gettext(
            "Change to heading %(heading_level)s `Ctrl+Shift+%(heading_level)s`",
        ),
        { heading_level: level },
    );

const getHeadingOptionTextContent = (level: number): string =>
    sprintf(gettext_provider.gettext("Heading %(heading_level)s"), { heading_level: level });

export const renderHeadingOption = (
    host: InternalHeadingsItem,
    level: number,
): UpdateFunction<InternalHeadingsItem> => html`
    <option
        onclick="${(): void => onClickApplyHeading(host, level)}"
        title="${getHeadingOptionTitle(level)}"
        selected="${isCurrentHeading(host, level)}"
    >
        ${getHeadingOptionTextContent(level)}
    </option>
`;

export const renderHeadingsOptions = (
    host: InternalHeadingsItem,
): UpdateFunction<InternalHeadingsItem> => {
    if (!host.style_elements.headings) {
        return html``;
    }

    const heading_options = [];
    for (let level = 1; level < NB_HEADING + 1; level++) {
        heading_options.push(renderHeadingOption(host, level));
    }
    return html`${heading_options}`;
};
