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

import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import type { GroupCollection } from "@tuleap/lazybox";
import { okAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import { setCatalog } from "../../../../../gettext-catalog";
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import type { RetrieveMatchingArtifact } from "../../../../../domain/fields/link-field/RetrieveMatchingArtifact";
import type { CurrentArtifactIdentifier } from "../../../../../domain/CurrentArtifactIdentifier";
import { LinkableArtifactStub } from "../../../../../../tests/stubs/LinkableArtifactStub";
import type { LinkableArtifact } from "../../../../../domain/fields/link-field/LinkableArtifact";
import { LinkTypeStub } from "../../../../../../tests/stubs/LinkTypeStub";
import { VerifyIsAlreadyLinkedStub } from "../../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";
import type { LinkField } from "../LinkField";
import type { RetrieveUserHistory } from "../../../../../domain/fields/link-field/RetrieveUserHistory";
import { RetrieveUserHistoryStub } from "../../../../../../tests/stubs/RetrieveUserHistoryStub";
import { UserIdentifierStub } from "../../../../../../tests/stubs/UserIdentifierStub";
import { SearchArtifactsStub } from "../../../../../../tests/stubs/SearchArtifactsStub";
import type { SearchArtifacts } from "../../../../../domain/fields/link-field/SearchArtifacts";
import { DispatchEventsStub } from "../../../../../../tests/stubs/DispatchEventsStub";
import type { LinkFieldController } from "../../../../../domain/fields/link-field/LinkFieldController";

const FIRST_ARTIFACT_ID = 1621;
const SECOND_ARTIFACT_ID = 15;
const FIRST_PARENT_ID = 429;
const FIRST_TITLE = "vancourier";
const SECOND_PARENT_ID = 748;
const SECOND_TITLE = "muriti";
const USER_ID = 102;

const ForbiddenFault = (): Fault => ({
    isForbidden: () => true,
    ...Fault.fromMessage("You don't have permission"),
});

const NotFoundFault = (): Fault => ({
    isNotFound: () => true,
    ...Fault.fromMessage("Artifact not found"),
});

describe("ArtifactLinkSelectorAutoCompleter", () => {
    let first_artifact: LinkableArtifact,
        second_artifact: LinkableArtifact,
        artifact_retriever: RetrieveMatchingArtifact,
        artifact_retriever_async: ResultAsync<LinkableArtifact, never>,
        current_artifact_option: Option<CurrentArtifactIdentifier>,
        user_history_retriever: RetrieveUserHistory,
        event_dispatcher: DispatchEventsStub,
        host: LinkField,
        artifacts_searcher: SearchArtifacts,
        get_parents_promise: Promise<LinkableArtifact[]>;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        first_artifact = LinkableArtifactStub.withCrossReference(
            FIRST_ARTIFACT_ID,
            "Do some stuff",
            `story #${FIRST_ARTIFACT_ID}`,
            "army-green",
        );
        second_artifact = LinkableArtifactStub.withCrossReference(
            SECOND_ARTIFACT_ID,
            "A110",
            `alp #${SECOND_ARTIFACT_ID}`,
            "daphne-blue",
        );

        artifact_retriever = RetrieveMatchingArtifactStub.withFault(NotFoundFault());
        user_history_retriever = RetrieveUserHistoryStub.withoutUserHistory();
        artifacts_searcher = SearchArtifactsStub.withoutResults();
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();

        current_artifact_option = Option.nothing();

        const initial_dropdown_content: GroupCollection = [];

        get_parents_promise = Promise.resolve([]);
        host = {
            current_link_type: LinkTypeStub.buildUntyped(),
            recently_viewed_section: initial_dropdown_content,
            matching_artifact_section: initial_dropdown_content,
            possible_parents_section: initial_dropdown_content,
            search_results_section: initial_dropdown_content,
            controller: {
                getPossibleParents: (): PromiseLike<ReadonlyArray<LinkableArtifact>> =>
                    get_parents_promise,
            } as LinkFieldController,
        } as LinkField;
    });

    const autocomplete = (query: string): void => {
        const autocompleter = ArtifactLinkSelectorAutoCompleter(
            artifact_retriever,
            VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked(),
            user_history_retriever,
            artifacts_searcher,
            event_dispatcher,
            current_artifact_option,
            UserIdentifierStub.fromUserId(USER_ID),
        );
        autocompleter.autoComplete(host, query);
    };

    describe(`given the selected type is NOT reverse _is_child`, () => {
        it.each([
            ["an empty string", ""],
            ["not a number", "I know I'm supposed to enter a number but I don't care"],
        ])(
            `when the query is %s, then it will set an empty matching artifact section
            and will clear the fault notification`,
            (query_content_type: string, query: string) => {
                autocomplete(query);

                expect(host.matching_artifact_section).toHaveLength(0);
            },
        );

        it(`when the query of the autocomplete is empty and the user has already seen some artifacts,
            then it will display the recently displayed group ONLY`, async () => {
            const user_history_async = okAsync([first_artifact, second_artifact]);
            user_history_retriever = RetrieveUserHistoryStub.withUserHistory(user_history_async);

            autocomplete("");

            const loading_groups = host.recently_viewed_section;
            expect(loading_groups).toHaveLength(1);
            expect(loading_groups[0].is_loading).toBe(true);

            await user_history_async;
            await user_history_async; //There are two level of promise

            expect(host.matching_artifact_section).toHaveLength(0);
            expect(host.search_results_section).toHaveLength(0);
            expect(host.possible_parents_section).toHaveLength(0);
            const group = host.recently_viewed_section[0];
            expect(group.items).toHaveLength(2);

            expect(group.items[0].value).toBe(first_artifact);
            expect(group.items[1].value).toBe(second_artifact);
        });

        it(`when the query has at least 3 characters,
            it will display the search results section`, async () => {
            const search_async = okAsync([first_artifact, second_artifact]);
            artifacts_searcher = SearchArtifactsStub.withResults(search_async);

            autocomplete("abc");

            await search_async;
            await search_async; //There are two level of promise

            expect(host.search_results_section).toHaveLength(1);
            const group = host.search_results_section[0];
            expect(group.items).toHaveLength(2);

            expect(group.items[0].value).toBe(first_artifact);
            expect(group.items[1].value).toBe(second_artifact);
        });

        it(`when the Search API responds 404 Not Found error code,
            it will not notify the fault as it means there is no Search backend installed
            and it will clear the search group so that the section does not appear at all`, async () => {
            artifacts_searcher = SearchArtifactsStub.withFault(NotFoundFault());

            await await autocomplete("abc");

            expect(event_dispatcher.getDispatchedEventTypes()).not.toContain("WillNotifyFault");
            expect(host.search_results_section).toHaveLength(0);
        });

        it(`when an unexpected error is returned by the API,
            then it will set the search results section with zero items so that link-selector can show the empty state message
            and will notify the fault`, async () => {
            artifacts_searcher = SearchArtifactsStub.withFault(Fault.fromMessage("Ooops"));

            await await autocomplete("abc");

            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillNotifyFault");
            expect(host.search_results_section).toHaveLength(1);
            const group = host.search_results_section[0];
            expect(group.items).toHaveLength(0);
            expect(group.empty_message).not.toBe("");
        });

        it(`when an artifact is returned by the artifact api,
            it will be added to the matching artifact section,
            and clear the fault notification`, async () => {
            const artifact_retriever_async = okAsync(first_artifact);
            artifact_retriever =
                RetrieveMatchingArtifactStub.withMatchingArtifact(artifact_retriever_async);

            autocomplete(String(FIRST_ARTIFACT_ID));

            const loading_groups = host.matching_artifact_section;
            expect(loading_groups).toHaveLength(1);
            expect(loading_groups[0].is_loading).toBe(true);

            await artifact_retriever_async;
            await artifact_retriever_async; //There are two level of promise

            const groups = host.matching_artifact_section;
            expect(groups).toHaveLength(1);
            expect(groups[0].is_loading).toBe(false);
            expect(groups[0].items).toHaveLength(1);

            const matching_artifact = groups[0].items[0];
            expect(matching_artifact.value).toBe(first_artifact);
        });

        it(`when an unexpected error is returned by the api (not code 403 or 404),
            then it will set a matching artifact with zero items so that link-selector can show the empty state message
            and notify the fault`, async () => {
            const fault = Fault.fromMessage("Nope");
            artifact_retriever = RetrieveMatchingArtifactStub.withFault(fault);

            autocomplete(String(FIRST_ARTIFACT_ID));

            await artifact_retriever_async;
            await artifact_retriever_async; //There are two level of promise

            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillNotifyFault");
            const groups = host.matching_artifact_section;
            expect(groups).toHaveLength(1);
            expect(groups[0].items).toHaveLength(0);
            expect(groups[0].empty_message).not.toBe("");
        });

        it(`when an unexpected error is returned by the api,
            then it will set the recently viewed artifact with zero items so that link-selector can show the empty state message
            and notify the fault`, async () => {
            const user_history_async = okAsync([second_artifact, first_artifact]);
            user_history_retriever = RetrieveUserHistoryStub.withUserHistory(user_history_async);
            const fault = Fault.fromMessage("Nope");
            user_history_retriever = RetrieveUserHistoryStub.withFault(fault);

            autocomplete("");

            await user_history_async;
            await user_history_async; //There are two level of promise

            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillNotifyFault");
            const groups = host.recently_viewed_section;
            expect(groups).toHaveLength(1);
            expect(groups[0].items).toHaveLength(0);
            expect(groups[0].empty_message).not.toBe("");
        });

        it.each([
            ["403 Forbidden error code", ForbiddenFault()],
            ["404 Not Found error code", NotFoundFault()],
        ])(
            `when the API responds %s,
            it will set an empty matching artifact group so that link-selector can show the empty state message
            and will not notify the fault as it is expected that it can fail
            (maybe the linkable number does not match any artifact)`,
            async (_type_of_error, fault) => {
                artifact_retriever = RetrieveMatchingArtifactStub.withFault(fault);

                autocomplete("404");

                await artifact_retriever_async;
                await artifact_retriever_async; //There are two level of promise

                expect(event_dispatcher.getDispatchedEventTypes()).not.toContain("WillNotifyFault");
                const groups = host.matching_artifact_section;
                expect(groups).toHaveLength(1);
                expect(groups[0].items).toHaveLength(0);
                expect(groups[0].empty_message).not.toBe("");
            },
        );
    });

    describe(`given the selected type is reverse _is_child`, () => {
        beforeEach(() => {
            host.current_link_type = LinkTypeStub.buildChildLinkType();
            get_parents_promise = Promise.resolve([
                LinkableArtifactStub.withDefaults({ id: FIRST_PARENT_ID, title: FIRST_TITLE }),
                LinkableArtifactStub.withDefaults({ id: SECOND_PARENT_ID, title: SECOND_TITLE }),
            ]);
        });

        it(`will retrieve the possible parents and set a group holding them`, async () => {
            autocomplete("");
            const loading_groups = host.possible_parents_section;
            expect(loading_groups).toHaveLength(1);
            expect(loading_groups[0].is_loading).toBe(true);

            await get_parents_promise;
            await get_parents_promise; //There are two levels of promise

            const groups = host.possible_parents_section;
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

        it.each([
            ["an empty string", "", 2],
            ["a string part of the Title of a possible parent", "uri", 2],
            ["a string not matching anything", "zzz", 0],
        ])(
            `when the query is %s, it will filter the possible parents on their title
                and it will set the dropdown content with a group containing matching parents`,
            async (_type_of_query, query, expected_number_of_matching_parents) => {
                autocomplete(query);

                await get_parents_promise;
                await get_parents_promise; //There are two levels of promise

                const groups = host.possible_parents_section;
                expect(groups).toHaveLength(1);
                expect(groups[0].items).toHaveLength(expected_number_of_matching_parents);
            },
        );

        it.each([
            ["a number part of the ID of a possible parent", "48", 1],
            ["a number not matching any parent", "999", 0],
        ])(
            `when the query is %s, it will filter the possible parents on their ID
                and it will also retrieve a matching artifact
                and it will set the second group containing matching parents`,
            async (_type_of_query, query, expected_number_of_matching_parents) => {
                autocomplete(query);

                await get_parents_promise;
                await get_parents_promise; //There are two levels of promise

                const parent_groups = host.possible_parents_section;
                expect(parent_groups).toHaveLength(1);
                expect(parent_groups[0].items).toHaveLength(expected_number_of_matching_parents);

                const matching_artifact_group = host.matching_artifact_section;
                expect(matching_artifact_group).toHaveLength(1);
            },
        );

        it(`when the query is a number, it will retrieve a matching artifact
                and also retrieve possible parents
                and it will set two groups holding each`, async () => {
            autocomplete(String(FIRST_ARTIFACT_ID));

            await get_parents_promise;
            await get_parents_promise; //There are two levels of promise

            const groups = host.possible_parents_section;
            expect(groups).toHaveLength(1);
            expect(groups[0].is_loading).toBe(false);

            const matching_artifact_group = host.matching_artifact_section;
            expect(matching_artifact_group).toHaveLength(1);
            expect(matching_artifact_group[0].is_loading).toBe(false);
        });
    });
});
