/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { setCatalog } from "../../../../gettext-catalog";
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { Fault } from "@tuleap/fault";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field-v2/RetrieveMatchingArtifact";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import { LinkSelectorStub } from "../../../../../tests/stubs/LinkSelectorStub";
import type { LinkableArtifact } from "../../../../domain/fields/link-field-v2/LinkableArtifact";
import { ClearFaultNotificationStub } from "../../../../../tests/stubs/ClearFaultNotificationStub";
import { NotifyFaultStub } from "../../../../../tests/stubs/NotifyFaultStub";

const ARTIFACT_ID = 1621;

const ForbiddenFault = (): Fault => ({
    isForbidden: () => true,
    ...Fault.fromMessage("You don't have permission"),
});

const NotFoundFault = (): Fault => ({
    isNotFound: () => true,
    ...Fault.fromMessage("Artifact not found"),
});

describe("ArtifactLinkSelectorAutoCompleter", () => {
    let artifact: LinkableArtifact,
        artifact_retriever: RetrieveMatchingArtifact,
        fault_notifier: NotifyFaultStub,
        notification_clearer: ClearFaultNotificationStub,
        link_selector: LinkSelectorStub,
        current_artifact_identifier: CurrentArtifactIdentifier | null;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        artifact = LinkableArtifactStub.withCrossReference(
            ARTIFACT_ID,
            "Do some stuff",
            `story #${ARTIFACT_ID}`,
            "army-green"
        );
        artifact_retriever = RetrieveMatchingArtifactStub.withMatchingArtifact(artifact);
        fault_notifier = NotifyFaultStub.withCount();
        notification_clearer = ClearFaultNotificationStub.withCount();
        link_selector = LinkSelectorStub.withDropdownContentRecord();
        current_artifact_identifier = null;
    });

    const autocomplete = async (query: string): Promise<void> => {
        const autocompleter = ArtifactLinkSelectorAutoCompleter(
            artifact_retriever,
            fault_notifier,
            notification_clearer,
            current_artifact_identifier
        );
        await autocompleter.autoComplete(link_selector, query);
    };

    it.each([
        ["an empty string", ""],
        ["not a number", "I know I'm supposed to enter a number by I don't care"],
    ])(
        `when the query is %s, it will set an empty group collection in link-selector
        and will clear the fault notification`,
        async (query_content_type: string, query: string) => {
            await autocomplete(query);

            expect(notification_clearer.getCallCount()).toBe(1);
            const groups = link_selector.getGroupCollection();
            expect(groups).toHaveLength(0);
        }
    );

    it(`when an artifact is returned by the api,
        then it will set a group with one item holding the matching artifact
        and clear the fault notification`, async () => {
        await autocomplete(String(ARTIFACT_ID));

        expect(notification_clearer.getCallCount()).toBe(1);
        const groups = link_selector.getGroupCollection();
        if (groups === undefined) {
            throw new Error("Expected a group collection to be set");
        }
        expect(groups).toHaveLength(1);
        expect(groups[0].items).toHaveLength(1);

        const first_item = groups[0].items[0];
        expect(first_item.value).toBe(artifact);
    });

    it(`when an unexpected error is returned by the api (not code 403 or 404),
        then it will set a group with zero items so that link-selector can show the empty state message
        and notify the fault`, async () => {
        const fault = Fault.fromMessage("Nope");
        artifact_retriever = RetrieveMatchingArtifactStub.withFault(fault);

        await autocomplete(String(ARTIFACT_ID));

        expect(notification_clearer.getCallCount()).toBe(1);
        expect(fault_notifier.getCallCount()).toBe(1);
        const groups = link_selector.getGroupCollection();
        if (groups === undefined) {
            throw new Error("Expected a group collection to be set");
        }
        expect(groups).toHaveLength(1);
        expect(groups[0].items).toHaveLength(0);
        expect(groups[0].empty_message).not.toBe("");
    });

    it.each([
        ["403 Forbidden error code", ForbiddenFault()],
        ["404 Not Found error code", NotFoundFault()],
    ])(
        `when the API responds %s,
        it will set a group with zero items so that link-selector can show the empty state message
        and will not notify the fault as it is expected that it can fail
        (maybe the linkable number does not match any artifact)`,
        async (_type_of_error, fault) => {
            artifact_retriever = RetrieveMatchingArtifactStub.withFault(fault);

            await autocomplete("404");

            expect(notification_clearer.getCallCount()).toBe(1);
            expect(fault_notifier.getCallCount()).toBe(0);
            const groups = link_selector.getGroupCollection();
            if (groups === undefined) {
                throw new Error("Expected a group collection to be set");
            }
            expect(groups).toHaveLength(1);
            expect(groups[0].items).toHaveLength(0);
            expect(groups[0].empty_message).not.toBe("");
        }
    );
});
