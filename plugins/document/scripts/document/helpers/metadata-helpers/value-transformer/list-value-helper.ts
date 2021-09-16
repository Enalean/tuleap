/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import type { Metadata, ListValue } from "../../../store/metadata/module";

export type MetadataListValue = Array<number> | Array<ListValue>;
export function assertListIsOnlyMultipleValue(
    list_value: MetadataListValue
): list_value is Array<ListValue> {
    return typeof list_value[0] === "object";
}

export function processFormattingOnKnownType(list_value: Array<ListValue>): Array<number> {
    const list_value_ids = list_value.map(({ id }) => id);

    return list_value_ids.length > 0 ? list_value_ids : [100];
}

export function formatMetadataMultipleValue(metadata: Metadata): Array<number> {
    if (!metadata.list_value || !assertListIsOnlyMultipleValue(metadata.list_value)) {
        return [100];
    }

    return processFormattingOnKnownType(metadata.list_value);
}

export function formatMetadataListValue(metadata: Metadata): number {
    if (!metadata.list_value || !assertListIsOnlyMultipleValue(metadata.list_value)) {
        return 100;
    }

    return metadata.list_value[0].id;
}
