/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import type { AllowedIcons, CsrfToken } from "./type";

export const PROJECT_ID: StrictInjectionKey<string> = Symbol("project_id");
export const MINIMAL_RANK: StrictInjectionKey<number> = Symbol("minimal_rank");
export const CSRF_TOKEN: StrictInjectionKey<CsrfToken> = Symbol("csrf_token");
export const ALLOWED_ICONS: StrictInjectionKey<AllowedIcons> = Symbol("allowed_icons");
