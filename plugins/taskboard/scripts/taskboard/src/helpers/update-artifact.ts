/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { UpdateCardPayload } from "../store/swimlane/card/type";
import { PutBody, TextFormat, TextValue } from "../store/swimlane/card/put-artifact-type";

export function getPutArtifactBody(payload: UpdateCardPayload): PutBody {
    if (!payload.tracker.title_field) {
        throw new Error("Unable to update the card title");
    }

    let value: string | TextValue;
    if (payload.tracker.title_field.is_string_field) {
        value = payload.label.replace(/(\r\n|\n|\r)+/gm, " ");
    } else {
        value = forceTextFormatForTextField(payload);
    }

    return {
        values: [
            {
                field_id: payload.tracker.title_field.id,
                value
            }
        ]
    };
}

function forceTextFormatForTextField(payload: UpdateCardPayload): TextValue {
    return {
        content: payload.label,
        format: TextFormat.TEXT
    };
}
