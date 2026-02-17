/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

import { type Result, ok, err } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { TransformedDropContext } from "./SuccessfulDropContextTransformer";
import { getFieldIndexInParent } from "./get-element-index";
import { isColumn } from "./is-column";
import type { MoveFieldsAPIRequestParams } from "./save-new-fields-order";
import { buildMoveFieldsAPIRequestParams } from "./build-move-fields-api-request-params";

export type FieldsMover = {
    moveField(
        transformed_drop_context: TransformedDropContext,
    ): Result<MoveFieldsAPIRequestParams, Fault>;
};

export const getFieldsMover = (): FieldsMover => ({
    moveField({
        moved_element,
        next_sibling,
        destination_parent,
        source_parent,
    }: TransformedDropContext): Result<MoveFieldsAPIRequestParams, Fault> {
        if (isColumn(moved_element)) {
            return err(Fault.fromMessage(`Element #${moved_element.field.field_id} is a column.`));
        }

        return getFieldIndexInParent(source_parent, moved_element)
            .andThen((index): Result<null, Fault> => {
                source_parent.children.splice(index, 1);
                return ok(null);
            })
            .andThen((): Result<MoveFieldsAPIRequestParams, Fault> => {
                if (next_sibling) {
                    return getFieldIndexInParent(destination_parent, next_sibling).andThen(
                        (index): Result<MoveFieldsAPIRequestParams, Fault> => {
                            destination_parent.children.splice(index, 0, moved_element);

                            return ok(
                                buildMoveFieldsAPIRequestParams(
                                    moved_element,
                                    destination_parent,
                                    next_sibling,
                                ),
                            );
                        },
                    );
                }

                destination_parent.children.push(moved_element);

                return ok(
                    buildMoveFieldsAPIRequestParams(
                        moved_element,
                        destination_parent,
                        next_sibling,
                    ),
                );
            });
    },
});
