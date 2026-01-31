/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import type { Ref } from "vue";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { User } from "@tuleap/core-rest-api-types";
import type { Option } from "@tuleap/option";
import type { ColorName } from "@tuleap/core-constants";
import type { ElementWithChildren } from "./type";

export const CURRENT_USER: StrictInjectionKey<Option<User>> = Symbol();
export const IS_USER_LOADING: StrictInjectionKey<boolean> = Symbol();
export const TRACKER_ID: StrictInjectionKey<number> = Symbol();
export const TRACKER_SHORTNAME: StrictInjectionKey<string> = Symbol();
export const TRACKER_COLOR: StrictInjectionKey<ColorName> = Symbol();
export const TRACKER_ROOT: StrictInjectionKey<Ref<ElementWithChildren>> = Symbol();
export const POST_FIELD_DND_CALLBACK: StrictInjectionKey<() => void> = Symbol();
