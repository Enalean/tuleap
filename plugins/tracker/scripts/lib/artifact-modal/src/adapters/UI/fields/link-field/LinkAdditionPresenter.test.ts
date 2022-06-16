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

import { LinkAdditionPresenter } from "./LinkAdditionPresenter";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";

describe(`LinkAdditionPresenter`, () => {
    it(`builds without selection`, () => {
        const presenter = LinkAdditionPresenter.withoutSelection();
        expect(presenter.is_add_button_disabled).toBe(true);
        expect(presenter.artifact).toBeNull();
    });

    it(`builds with an artifact selected`, () => {
        const artifact = LinkableArtifactStub.withDefaults();
        const presenter = LinkAdditionPresenter.withArtifactSelected(artifact);
        expect(presenter.is_add_button_disabled).toBe(false);
        expect(presenter.artifact).toBe(artifact);
    });
});
