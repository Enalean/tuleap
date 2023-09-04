/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { InjectionKey } from "vue";
import type { FeedbackHandler, NewItemAlternativeArray } from "./type";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";

export const FEEDBACK: InjectionKey<FeedbackHandler> = Symbol("feedback");
export const SHOULD_DISPLAY_HISTORY_IN_DOCUMENT: StrictInjectionKey<boolean> = Symbol(
    "should_display_history_in_document",
);
export const SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS: StrictInjectionKey<boolean> = Symbol(
    "should_display_source_column_for_versions",
);
export const NEW_ITEMS_ALTERNATIVES: StrictInjectionKey<NewItemAlternativeArray> = Symbol(
    "create_new_item_alternatives",
);
