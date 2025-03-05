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

import { OPTION_PLAIN_TEXT } from "./plain-text-option-template";
import { OPTION_PREFORMATTED } from "./preformatted-text-option-template";
import {
    isCurrentHeading,
    OPTION_HEADING_1,
    OPTION_HEADING_2,
    OPTION_HEADING_3,
} from "./heading-option-template";
import { OPTION_SUBTITLE } from "./subtitle-option-template";
import type { InternalTextStyleItem } from "./text-style";

const applyPlainText = (host: InternalTextStyleItem): void => {
    if (host.is_plain_text_activated) {
        return;
    }

    host.toolbar_bus.plainText();
};

const applyPreformattedText = (host: InternalTextStyleItem): void => {
    if (host.is_preformatted_text_activated) {
        return;
    }

    host.toolbar_bus.preformattedText();
};

const applyHeading = (host: InternalTextStyleItem, level: number): void => {
    if (isCurrentHeading(host, level)) {
        return;
    }

    host.toolbar_bus.heading({ level });
};

const applySubtitle = (host: InternalTextStyleItem): void => {
    if (host.is_subtitle_activated) {
        return;
    }

    host.toolbar_bus.subtitle();
};

export const applyTextStyle = (
    host: InternalTextStyleItem,
    selected_option_value: string,
): void => {
    switch (selected_option_value) {
        case OPTION_PLAIN_TEXT:
            applyPlainText(host);
            break;
        case OPTION_PREFORMATTED:
            applyPreformattedText(host);
            break;
        case OPTION_SUBTITLE:
            applySubtitle(host);
            break;
        case OPTION_HEADING_1:
            applyHeading(host, 1);
            break;
        case OPTION_HEADING_2:
            applyHeading(host, 2);
            break;
        case OPTION_HEADING_3:
            applyHeading(host, 3);
            break;
        default:
            break;
    }
};
