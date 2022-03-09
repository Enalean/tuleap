/**
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

import { initModal } from "./init-modal";
import type { GlobalExportProperties } from "./type";
import * as vue from "vue";
import type { App } from "vue";

describe("init-modal", () => {
    it("initializes the modal multiple times", () => {
        const element = document.createElement("div");

        const spy_create_app = jest.spyOn(vue, "createApp");
        const spy_unmount = jest.fn();
        spy_create_app.mockReturnValue({
            mount: jest.fn(),
            unmount: spy_unmount,
        } as unknown as App);

        initModal(element, {} as GlobalExportProperties);
        initModal(element, {} as GlobalExportProperties);

        expect(spy_create_app).toBeCalledTimes(2);
        expect(spy_unmount).toHaveBeenCalled();
    });
});
