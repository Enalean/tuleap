/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import type { Step } from "./Step";

export const PROJECT_ID: StrictInjectionKey<number> = Symbol("project_id");
export const FIELD_ID: StrictInjectionKey<number> = Symbol("field_id");
export const EMPTY_STEP: StrictInjectionKey<Step> = Symbol("empty_step");
export const UPLOAD_URL: StrictInjectionKey<string> = Symbol("upload_url");
export const UPLOAD_FIELD_NAME: StrictInjectionKey<string> = Symbol("upload_field_name");
export const UPLOAD_MAX_SIZE: StrictInjectionKey<string> = Symbol("upload_max_size");
export const IS_DRAGGING: StrictInjectionKey<Ref<boolean>> = Symbol("is_dragging");
