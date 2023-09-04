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

import type { DomainEvent } from "../../DomainEvent";
import type { BindValueId } from "./BindValueId";

export type DidChangeListFieldValue = DomainEvent<"DidChangeListFieldValue"> & {
    readonly field_id: number;
    readonly bind_value_ids: ReadonlyArray<BindValueId>;
};

export const DidChangeListFieldValue = (
    field_id: number,
    bind_value_ids: ReadonlyArray<BindValueId>,
): DidChangeListFieldValue => ({
    type: "DidChangeListFieldValue",
    field_id,
    bind_value_ids,
});
