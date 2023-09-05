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

import type { HTMLTemplateStringProcessor, LazyboxItem, HTMLTemplateResult } from "@tuleap/lazybox";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";

const isAssignableReviewer = (item_value: unknown): item_value is User =>
    typeof item_value === "object" && item_value !== null && "id" in item_value;

export const getAssignableReviewer = (item_value: unknown): User | null => {
    if (!isAssignableReviewer(item_value)) {
        return null;
    }
    return item_value;
};

export const getSelectedReviewers = (selected_users: unknown): User[] => {
    if (!Array.isArray(selected_users)) {
        return [];
    }

    return selected_users.reduce((acc: User[], user) => {
        const reviewer = getAssignableReviewer(user);
        if (reviewer) {
            acc.push(reviewer);
        }
        return acc;
    }, []);
};

export const getAssignableReviewerTemplate = (
    lit_html: typeof HTMLTemplateStringProcessor,
    item: LazyboxItem,
): HTMLTemplateResult => {
    const reviewer = getAssignableReviewer(item.value);
    if (!reviewer) {
        return lit_html``;
    }

    return lit_html`
        <span class="pull-request-reviewers-badge">
            <div class="tlp-avatar-mini">
                <img src="${reviewer.avatar_url}" />
            </div>
            ${reviewer.display_name}
        </span>
    `;
};
