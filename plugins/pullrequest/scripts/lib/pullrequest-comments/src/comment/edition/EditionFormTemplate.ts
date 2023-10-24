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

import { html, dispatch } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { GettextProvider } from "@tuleap/gettext";
import type { HostElement } from "./EditionForm";

export const getEditionForm = (
    host: HostElement,
    gettext_provider: GettextProvider,
): UpdateFunction<HostElement> => html`
    ${host.writing_zone}
    <div class="pull-request-comment-footer" data-test="pull-request-description-comment-footer">
        <button
            data-test="button-cancel-edition"
            type="button"
            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
            onclick="${(): void => {
                dispatch(host, "cancel-edition");
            }}"
        >
            ${gettext_provider.gettext("Cancel")}
        </button>
        <button
            type="button"
            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary"
            disabled
            title="Comment edition is under implementation"
        >
            ${gettext_provider.gettext("Save")}
        </button>
    </div>
`;
