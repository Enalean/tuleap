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

import type { HTMLTemplateStringProcessor, HTMLTemplateResult, LazyboxItem } from "@tuleap/lazybox";
import { isUser } from "./UserTypeGuard";

export const UserTemplatingCallback = (
    html: typeof HTMLTemplateStringProcessor,
    item: LazyboxItem,
): HTMLTemplateResult => {
    if (!isUser(item.value)) {
        return html``;
    }

    return html`
        <span class="pull-request-autocompleter-avatar" data-test="pull-request-user">
            <div class="tlp-avatar-mini">
                <img src="${item.value.avatar_url}" data-test="pull-request-user-avatar" />
            </div>
            ${item.value.display_name}
        </span>
    `;
};
