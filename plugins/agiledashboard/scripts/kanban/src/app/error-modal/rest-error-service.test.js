/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import angular from "angular";
import "angular-mocks";
import error_modal_module from "./error-modal.js";

describe("RestErrorService", () => {
    let RestErrorService, TlpModalService;

    beforeEach(() => {
        angular.mock.module(error_modal_module);
        angular.mock.inject(function (_TlpModalService_, _RestErrorService_) {
            TlpModalService = _TlpModalService_;
            RestErrorService = _RestErrorService_;
        });
    });

    describe("reload()", () => {
        it(`Given a REST error,
            then a modal will be opened to inform the user that she must reload the page`, async () => {
            const openModal = jest.spyOn(TlpModalService, "open").mockImplementation(() => {});
            const error = new Error();
            error.response = {
                json: () => Promise.resolve({ error: { code: 401, message: "Unauthorized" } }),
            };

            await RestErrorService.reload(error);

            expect(openModal).toHaveBeenCalled();
        });
    });
});
