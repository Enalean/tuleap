/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

import type { LazyboxItem, GroupOfItems } from "@tuleap/lazybox";
import { getOpenStaticValue } from "./open-static-list-value-getter";
import type { VueGettextProvider } from "../../../helpers/vue-gettext-provider";

export function handleStaticListValueFilter(
    query: string,
    values: LazyboxItem[],
    gettext_provider: VueGettextProvider,
): GroupOfItems[] {
    const trimmed_query = query.trim();
    const items =
        trimmed_query !== ""
            ? values.filter((item) =>
                  getOpenStaticValue(item.value)
                      ?.label.toLowerCase()
                      .includes(trimmed_query.toLowerCase()),
              )
            : values;
    return [
        {
            label: gettext_provider.$gettext("Static values"),
            empty_message: gettext_provider.$gettext("No value"),
            is_loading: false,
            items,
            footer_message: "",
        },
    ];
}
