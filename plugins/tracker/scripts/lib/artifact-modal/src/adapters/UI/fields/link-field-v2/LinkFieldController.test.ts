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

import type { LinkFieldPresenter } from "./LinkFieldPresenter";
import { LinkFieldController } from "./LinkFieldController";
import type { LinkedArtifact } from "../../../../domain/fields/link-field-v2/LinkedArtifact";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import type { RetrieveAllLinkedArtifacts } from "../../../../domain/fields/link-field-v2/RetrieveAllLinkedArtifacts";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";

describe(`LinkFieldController`, () => {
    describe(`displayLinkedArtifacts()`, () => {
        let artifact_id: CurrentArtifactIdentifier | null,
            links_retriever: RetrieveAllLinkedArtifacts;

        beforeEach(() => {
            artifact_id = CurrentArtifactIdentifierStub.withId(18);
            const linked_artifact = { title: "Child" } as LinkedArtifact;
            links_retriever = RetrieveAllLinkedArtifactsStub.withLinkedArtifacts(linked_artifact);
        });

        const displayLinkedArtifacts = (): Promise<LinkFieldPresenter> => {
            const controller = LinkFieldController(links_retriever, artifact_id);
            return controller.displayLinkedArtifacts();
        };

        it(`when the modal is in creation mode, it will return a dedicated empty presenter`, async () => {
            artifact_id = null;
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
        });

        it(`when the modal is in edition mode and it succeeds loading,
            it will return a presenter with the linked artifacts`, async () => {
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(presenter.error_message).toBe("");
        });

        it(`when the modal is in edition mode and it fails loading,
            it will return a presenter with an error message`, async () => {
            const error_message = "Ooops";
            links_retriever = RetrieveAllLinkedArtifactsStub.withError(error_message);
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(presenter.error_message).toBe(error_message);
        });
    });
});
