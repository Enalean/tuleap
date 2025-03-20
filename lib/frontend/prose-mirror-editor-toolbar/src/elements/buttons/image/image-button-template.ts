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
import type { InternalImageButton } from "./image";
import { getClass } from "../../../helpers/class-getter";
import type { GetText } from "@tuleap/gettext";

export const renderImageButton = (
    host: InternalImageButton,
    gettext_provider: GetText,
): UpdateFunction<InternalImageButton> => html`
    <button
        class="${getClass(host)}"
        data-role="popover-trigger"
        disabled="${host.is_disabled}"
        title="${gettext_provider.gettext("Insert or edit image")}"
        data-test="button-image"
    >
        <i class="prose-mirror-toolbar-button-icon fa-solid fa-image"></i>
    </button>
`;
