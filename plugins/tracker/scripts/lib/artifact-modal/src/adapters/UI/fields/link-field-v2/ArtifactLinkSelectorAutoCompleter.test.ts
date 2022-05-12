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
import type { RetrieveSelectedLinkType } from "../../../../domain/fields/link-field-v2/RetrieveSelectedLinkType";
import { RetrieveSelectedLinkTypeStub } from "../../../../../tests/stubs/RetrieveSelectedLinkTypeStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field-v2/RetrievePossibleParents";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import type { CurrentTrackerIdentifier } from "../../../../domain/CurrentTrackerIdentifier";
import type { GroupCollection } from "@tuleap/link-selector";

const ARTIFACT_ID = 1621;
const TRACKER_ID = 978;
const FIRST_PARENT_ID = 429;
const FIRST_TITLE = "vancourier";
const SECOND_PARENT_ID = 748;
const SECOND_TITLE = "muriti";

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
        type_retriever: RetrieveSelectedLinkType,
        parents_retriever: RetrievePossibleParents,
        current_artifact_identifier: CurrentArtifactIdentifier | null,
        current_tracker_identifier: CurrentTrackerIdentifier;

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
        type_retriever = RetrieveSelectedLinkTypeStub.withType(LinkTypeStub.buildUntyped());
        parents_retriever = RetrievePossibleParentsStub.withoutParents();
        current_artifact_identifier = null;
        current_tracker_identifier = CurrentTrackerIdentifierStub.withId(TRACKER_ID);
    });

    const getGroupCollection = (): GroupCollection => {
        const groups = link_selector.getGroupCollection();
        if (groups === undefined) {
            throw new Error("Expected a group collection to be set");
        }
        return groups;
    };

    const autocomplete = async (query: string): Promise<void> => {
        const autocompleter = ArtifactLinkSelectorAutoCompleter(
            artifact_retriever,
            fault_notifier,
            notification_clearer,
            type_retriever,
            parents_retriever,
            current_artifact_identifier,
            current_tracker_identifier
        );
        await autocompleter.autoComplete(link_selector, query);
    };

    describe(`given the selected type is NOT reverse _is_child`, () => {
        it.each([
            ["an empty string", ""],
            ["not a number", "I know I'm supposed to enter a number but I don't care"],
        ])(
            `when the query is %s, it will set an empty group collection in link-selector
            and will clear the fault notification`,
            async (query_content_type: string, query: string) => {
                await autocomplete(query);

                expect(notification_clearer.getCallCount()).toBe(1);
                const groups = getGroupCollection();
                expect(groups).toHaveLength(0);
            }
        );

        it(`when an artifact is returned by the api,
            then it will set a group with one item holding the matching artifact
            and clear the fault notification`, async () => {
            const promise = autocomplete(String(ARTIFACT_ID));

            expect(notification_clearer.getCallCount()).toBe(1);
            const loading_groups = getGroupCollection();
            expect(loading_groups).toHaveLength(1);
            expect(loading_groups[0].is_loading).toBe(true);

            await promise;

            const groups = getGroupCollection();
            expect(groups).toHaveLength(1);
            expect(groups[0].is_loading).toBe(false);
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
            const groups = getGroupCollection();
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
                const groups = getGroupCollection();
                expect(groups).toHaveLength(1);
                expect(groups[0].items).toHaveLength(0);
                expect(groups[0].empty_message).not.toBe("");
            }
        );
    });

    describe(`given the selected type is reverse _is_child`, () => {
        beforeEach(() => {
            type_retriever = RetrieveSelectedLinkTypeStub.withType(
                LinkTypeStub.buildParentLinkType()
            );
            parents_retriever = RetrievePossibleParentsStub.withParents(
                LinkableArtifactStub.withDefaults({ id: FIRST_PARENT_ID, title: FIRST_TITLE }),
                LinkableArtifactStub.withDefaults({ id: SECOND_PARENT_ID, title: SECOND_TITLE })
            );
        });

        it(`will retrieve the possible parents and set a group holding them
            and clear the fault notification`, async () => {
            const promise = autocomplete("");

            expect(notification_clearer.getCallCount()).toBe(1);
            const loading_groups = getGroupCollection();
            expect(loading_groups).toHaveLength(1);
            expect(loading_groups[0].is_loading).toBe(true);

            await promise;

            const groups = getGroupCollection();
            expect(groups).toHaveLength(1);
            expect(groups[0].is_loading).toBe(false);
            const parent_ids = groups[0].items.map((item) => {
                const linkable_artifact = item.value as LinkableArtifact;
                return linkable_artifact.id;
            });
            expect(parent_ids).toHaveLength(2);
            expect(parent_ids).toContain(FIRST_PARENT_ID);
            expect(parent_ids).toContain(SECOND_PARENT_ID);
        });

        it(`when there is an error during retrieval of the possible parents,
            it will notify that there has been a fault
            and will set the dropdown content with an empty group of possible parents`, async () => {
            parents_retriever = RetrievePossibleParentsStub.withFault(Fault.fromMessage("Ooops"));

            await autocomplete("irrelevant");

            expect(fault_notifier.getCallCount()).toBe(1);
            const groups = getGroupCollection();
            expect(groups).toHaveLength(1);
            expect(groups[0].is_loading).toBe(false);
            expect(groups[0].items).toHaveLength(0);
        });

        it.each([
            ["an empty string", "", 2],
            ["a string part of the Title of a possible parent", "uri", 2],
            ["a string not matching anything", "zzz", 0],
        ])(
            `when the query is %s, it will filter the possible parents on their title
            and it will set the dropdown content with a group containing matching parents`,
            async (_type_of_query, query, expected_number_of_matching_parents) => {
                await autocomplete(query);

                const groups = getGroupCollection();
                expect(groups).toHaveLength(1);
                expect(groups[0].items).toHaveLength(expected_number_of_matching_parents);
            }
        );

        it.each([
            ["a number part of the ID of a possible parent", "48", 1],
            ["a number not matching any parent", "999", 0],
        ])(
            `when the query is %s, it will filter the possible parents on their ID
            and it will also retrieve a matching artifact
            and it will set the second group containing matching parents`,
            async (_type_of_query, query, expected_number_of_matching_parents) => {
                await autocomplete(query);

                const groups = getGroupCollection();
                expect(groups).toHaveLength(2);
                expect(groups[1].items).toHaveLength(expected_number_of_matching_parents);
            }
        );

        it(`when the query is a number, it will retrieve a matching artifact
            and also retrieve possible parents
            and it will set two groups holding each`, async () => {
            const promise = autocomplete(String(ARTIFACT_ID));

            const loading_groups = getGroupCollection();
            expect(loading_groups).toHaveLength(2);
            expect(loading_groups.every((group) => group.is_loading)).toBe(true);

            await promise;

            const groups = getGroupCollection();
            expect(groups).toHaveLength(2);
            expect(groups.every((group) => group.is_loading)).toBe(false);
        });
    });
});
