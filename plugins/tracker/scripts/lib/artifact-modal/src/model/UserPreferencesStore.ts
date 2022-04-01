/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import { TEXT_FORMAT_TEXT } from "@tuleap/plugin-tracker-constants";

let text_fields_default_format: TextFieldFormat = TEXT_FORMAT_TEXT;

export const setTextFieldDefaultFormat = (new_format: TextFieldFormat): void => {
    text_fields_default_format = new_format;
};

export const getTextFieldDefaultFormat = (): TextFieldFormat => text_fields_default_format;
