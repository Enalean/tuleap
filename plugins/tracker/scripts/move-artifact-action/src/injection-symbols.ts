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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { ColorName } from "@tuleap/core-constants";

export const TRACKER_ID: StrictInjectionKey<number> = Symbol("tracker_id");
export const TRACKER_NAME: StrictInjectionKey<string> = Symbol("tracker_name");
export const TRACKER_COLOR: StrictInjectionKey<ColorName> = Symbol("tracker_color");
export const ARTIFACT_ID: StrictInjectionKey<number> = Symbol("artifact_id");
export const PROJECT_ID: StrictInjectionKey<number> = Symbol("project_id");
