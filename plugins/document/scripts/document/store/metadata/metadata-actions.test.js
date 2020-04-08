/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { mockFetchError } from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import { loadProjectMetadata } from "./metadata-actions.js";
import * as rest_querier from "../../api/rest-querier.js";
import * as handle_errors from "../actions-helpers/handle-errors.js";

describe("Metadata actions", () => {
    let context, getProjectMetadata, handleErrors, global_context;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
        };

        global_context = {
            state: {
                project_id: 102,
            },
        };
        getProjectMetadata = jest.spyOn(rest_querier, "getProjectMetadata");
        handleErrors = jest.spyOn(handle_errors, "handleErrors").mockReturnValue(Promise.resolve());
    });

    it(`load project metadata definition`, async () => {
        const metadata = [
            {
                short_name: "text",
                type: "text",
            },
        ];

        getProjectMetadata.mockReturnValue(metadata);

        await loadProjectMetadata(context, [global_context]);

        expect(context.commit).toHaveBeenCalledWith("saveProjectMetadata", metadata);
    });

    it("Handle error when metadata project load fails", async () => {
        mockFetchError(getProjectMetadata, {
            status: 400,
            error_json: {
                error: {
                    message: "Something bad happens",
                },
            },
        });

        await loadProjectMetadata(context, [{ state: { project_id: 102 } }]);

        expect(handleErrors).toHaveBeenCalled();
    });
});
