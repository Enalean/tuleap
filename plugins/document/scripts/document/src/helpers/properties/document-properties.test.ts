/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { getDocumentProperties } from "./document-properties";
import type { State } from "../../type";
import type { ActionContext } from "vuex";
import * as rest_querier from "../../api/rest-querier";
import { FolderBuilder } from "../../../tests/builders/FolderBuilder";

describe("document-properties", () => {
    const document_properties = getDocumentProperties();
    let context: ActionContext<State, State>;

    beforeEach(() => {
        context = {
            commit: vi.fn(),
            dispatch: vi.fn(),
        } as unknown as ActionContext<State, State>;
    });

    describe("getFolderProperties", () => {
        it("Given a folder item, it's properties are fetched and returned", async () => {
            const getItemWithSize = vi.spyOn(rest_querier, "getItemWithSize").mockReturnValue(
                Promise.resolve(
                    new FolderBuilder(3)
                        .withTitle("Project Documentation")
                        .withProperties({
                            total_size: 102546950,
                            nb_files: 27,
                        })
                        .build(),
                ),
            );

            const properties = await document_properties.getFolderProperties(
                context,
                new FolderBuilder(3).withTitle("Project Documentation").build(),
            );

            expect(getItemWithSize).toHaveBeenCalled();
            expect(properties).toStrictEqual({
                total_size: 102546950,
                nb_files: 27,
            });
        });

        it("Handles errors when it fails", async () => {
            const getItemWithSize = vi
                .spyOn(rest_querier, "getItemWithSize")
                .mockReturnValue(Promise.reject("error"));

            const folder = await document_properties.getFolderProperties(
                context,
                new FolderBuilder(3).withTitle("Project Documentation").build(),
            );

            expect(getItemWithSize).toHaveBeenCalled();
            expect(folder).toBeNull();
            expect(context.dispatch).toHaveBeenCalled();
        });
    });
});
