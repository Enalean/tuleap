/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import type { DateTimeFormat, LocaleString } from "@tuleap/core-constants";

export type CommentUserPreferences = {
    readonly is_comment_order_inverted: boolean;
    readonly date_time_format: DateTimeFormat;
    readonly locale: LocaleString;
    readonly relative_dates_display: RelativeDatesDisplayPreference;
    readonly is_allowed_to_add_comment: boolean;
    readonly are_mentions_effective: boolean;
    readonly text_format: TextFieldFormat;
};
