/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject/src/strict-inject";

export type ConfigurationTab = "tracker-selection-tab" | "readonly-fields-selection-tab";

export const TRACKER_SELECTION_TAB: ConfigurationTab = "tracker-selection-tab";
export const READONLY_FIELDS_SELECTION_TAB: ConfigurationTab = "readonly-fields-selection-tab";

export const CLOSE_CONFIGURATION_MODAL: StrictInjectionKey<() => void> = Symbol(
    "close-configuration-modal",
);
