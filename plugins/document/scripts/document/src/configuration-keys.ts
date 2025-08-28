/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";

export const USER_ID: StrictInjectionKey<number> = Symbol("user-id");
export const PROJECT_ID: StrictInjectionKey<number> = Symbol("project-id");
export const ROOT_ID: StrictInjectionKey<number> = Symbol("root-id");
export const PROJECT_NAME: StrictInjectionKey<string> = Symbol("project-name");
export const PROJECT_PUBLIC_NAME: StrictInjectionKey<string> = Symbol("project-public-name");
export const USER_IS_ADMIN: StrictInjectionKey<boolean> = Symbol("user-is-admin");
export const USER_CAN_CREATE_WIKI: StrictInjectionKey<boolean> = Symbol("user-can-create-wiki");
export const EMBEDDED_ARE_ALLOWED: StrictInjectionKey<boolean> = Symbol("embedded-are-allowed");
export const IS_STATUS_PROPERTY_USED: StrictInjectionKey<boolean> =
    Symbol("is-status-property-used");
