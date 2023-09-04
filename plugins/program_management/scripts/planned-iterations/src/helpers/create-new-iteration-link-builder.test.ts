/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import {
    buildIterationCreationUrl,
    buildIterationEditionUrl,
} from "./create-new-iteration-link-builder";

describe("create-new-iteration-link-builder", () => {
    it("should build the iteration creation url", () => {
        expect(buildIterationCreationUrl(1280, 101)).toBe(
            "/plugins/tracker/?redirect-to-planned-iterations=create&increment-id=1280&tracker=101&func=new-artifact",
        );
    });

    it("should build the iteration edition url", () => {
        expect(buildIterationEditionUrl(1281, 1280)).toBe(
            "/plugins/tracker/?aid=1281&redirect-to-planned-iterations=update&increment-id=1280",
        );
    });
});
