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

import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import { TEXT_FORMAT_COMMONMARK } from "@tuleap/plugin-tracker-constants";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import { PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP } from "@tuleap/tlp-relative-date";
import type { CommentUserPreferences } from "../../src/domain/comments/CommentUserPreferences";
import type { DateTimeFormat, LocaleString } from "@tuleap/core-constants";
import { en_US_DATE_TIME_FORMAT, en_US_LOCALE } from "@tuleap/core-constants";

export class CommentUserPreferencesBuilder {
    #is_comment_order_inverted = false;
    #is_allowed_to_add_comment = true;
    #text_format: TextFieldFormat = TEXT_FORMAT_COMMONMARK;
    #date_time_format: DateTimeFormat = en_US_DATE_TIME_FORMAT;
    #locale: LocaleString = en_US_LOCALE;
    #relative_dates_display: RelativeDatesDisplayPreference =
        PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP;
    #are_mentions_effective = true;

    private constructor() {
        // Prefer static method for instantiation
    }

    static userPreferences(): CommentUserPreferencesBuilder {
        return new CommentUserPreferencesBuilder();
    }

    withNewCommentAllowed(is_allowed_to_add_comment: boolean): this {
        this.#is_allowed_to_add_comment = is_allowed_to_add_comment;
        return this;
    }

    withDefaultTextFormat(text_format: TextFieldFormat): this {
        this.#text_format = text_format;
        return this;
    }

    withLocale(locale: LocaleString): this {
        this.#locale = locale;
        return this;
    }

    withRelativeDatesDisplay(display_preference: RelativeDatesDisplayPreference): this {
        this.#relative_dates_display = display_preference;
        return this;
    }

    build(): CommentUserPreferences {
        return {
            is_allowed_to_add_comment: this.#is_allowed_to_add_comment,
            are_mentions_effective: this.#are_mentions_effective,
            is_comment_order_inverted: this.#is_comment_order_inverted,
            relative_dates_display: this.#relative_dates_display,
            date_time_format: this.#date_time_format,
            text_format: this.#text_format,
            locale: this.#locale,
        };
    }
}
