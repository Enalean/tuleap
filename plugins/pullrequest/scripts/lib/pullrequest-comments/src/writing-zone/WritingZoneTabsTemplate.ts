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
import type { WritingZone } from "./WritingZone";
import type { WritingZonePresenter } from "./WritingZonePresenter";
import "../writing-zone/WritingZone";

type WritingZoneTabName = "write" | "preview";

const TAB_WRITE: WritingZoneTabName = "write";
const TAB_PREVIEW: WritingZoneTabName = "preview";

const isTabActive = (tab_name: WritingZoneTabName, presenter: WritingZonePresenter): boolean => {
    if (!presenter.is_focused) {
        return false;
    }

    return (
        (tab_name === TAB_PREVIEW && presenter.is_in_preview_mode) ||
        (tab_name === TAB_WRITE && presenter.is_in_writing_mode)
    );
};

export const buildWriteTab = (
    host: WritingZone,
    gettext_provider: GettextProvider,
): UpdateFunction<WritingZone> => {
    const tabs_classes = {
        "tlp-tab": true,
        "tlp-tab-active": isTabActive(TAB_WRITE, host.presenter),
    };

    return html`
        <span
            data-test="writing-tab"
            class="${tabs_classes}"
            onclick="${host.controller.switchToWritingMode}"
        >
            ${gettext_provider.gettext("Write")}
        </span>
    `;
};

export const buildPreviewTab = (
    host: WritingZone,
    gettext_provider: GettextProvider,
): UpdateFunction<WritingZone> => {
    const tabs_classes = {
        "tlp-tab": true,
        "tlp-tab-active": isTabActive(TAB_PREVIEW, host.presenter),
    };

    return html`
        <span
            data-test="preview-tab"
            class="${tabs_classes}"
            onclick="${host.controller.switchToPreviewMode}"
        >
            ${gettext_provider.gettext("Preview")}
        </span>
    `;
};
