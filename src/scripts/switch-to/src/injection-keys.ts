/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import type { SearchForm } from "./type";

export const ARE_RESTRICTED_USERS_ALLOWED: InjectionKey<boolean> = Symbol();
export const IS_TROVE_CAT_ENABLED: InjectionKey<boolean> = Symbol();
export const SEARCH_FORM: InjectionKey<SearchForm> = Symbol();
export const IS_SEARCH_AVAILABLE: InjectionKey<boolean> = Symbol();
