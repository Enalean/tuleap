/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import artifact_modal_module from "./tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";
import { fr_FR_DATE_TIME_FORMAT, fr_FR_LOCALE } from "@tuleap/core-constants";
import { TEXT_FORMAT_COMMONMARK } from "@tuleap/plugin-tracker-constants";
import * as modal_creation_mode_state from "./modal-creation-mode-state.ts";
import * as rest_service from "./rest/rest-service";
import * as form_tree_builder from "./model/form-tree-builder.js";
import * as workflow_field_values_filter from "./model/workflow-field-values-filter.js";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";
import * as field_values_formatter from "./model/field-values-formatter.js";
import * as tracker_transformer from "./model/tracker-transformer.js";
import { RetrieveCurrentArtifactWithTrackerStructureStub } from "../tests/stubs/RetrieveCurrentArtifactWithTrackerStructureStub";

const USER_LOCALE = fr_FR_LOCALE;
const USER_DATE_TIME_FORMAT = fr_FR_DATE_TIME_FORMAT;

describe("NewTuleapArtifactModalService", () => {
    let NewTuleapArtifactModalService,
        $q,
        buildFormTree,
        enforceWorkflowTransitions,
        setCreationMode,
        isInCreationMode,
        getTracker,
        getUserPreference,
        tracker,
        wrapPromise,
        getSelectedValues;

    const USER_ID = 103;

    beforeEach(() => {
        angular.mock.module(artifact_modal_module);

        let $rootScope;
        angular.mock.inject((_$rootScope_, _$q_, _NewTuleapArtifactModalService_) => {
            $rootScope = _$rootScope_;
            $q = _$q_;
            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
        });

        jest.spyOn(tracker_transformer, "addFieldValuesToTracker").mockImplementation(
            (artifact_values, tracker) => {
                return tracker;
            },
        );

        jest.spyOn(tracker_transformer, "transform").mockImplementation((tracker) => {
            return { ...tracker, ordered_fields: [] };
        });
        setCreationMode = jest.spyOn(modal_creation_mode_state, "setCreationMode");
        isInCreationMode = jest.spyOn(modal_creation_mode_state, "isInCreationMode");
        getTracker = jest.spyOn(rest_service, "getTracker");
        getUserPreference = jest.spyOn(rest_service, "getUserPreference");
        buildFormTree = jest.spyOn(form_tree_builder, "buildFormTree").mockReturnValue({});
        enforceWorkflowTransitions = jest.spyOn(
            workflow_field_values_filter,
            "enforceWorkflowTransitions",
        );
        getSelectedValues = jest
            .spyOn(field_values_formatter, "getSelectedValues")
            .mockReturnValue({});

        document.body.dataset.userLocale = USER_LOCALE;
        document.body.dataset.dateTimeFormat = USER_DATE_TIME_FORMAT;

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(() => {
        document.body.dataset.userLocale = undefined;
        document.body.dataset.dateTimeFormat = undefined;
    });

    describe("initCreationModalModel() -", () => {
        const TRACKER_ID = 28,
            PARENT_ARTIFACT_ID = 581;

        it(`Given a tracker id and a parent artifact id,
            then the tracker's structure will be retrieved
            and a promise will be resolved with the modal's model object`, async () => {
            tracker = {
                id: TRACKER_ID,
                color_name: "importer",
                item_name: "preinvest",
                parent: null,
                fields: [],
            };
            getTracker.mockReturnValue($q.when(tracker));

            const promise = NewTuleapArtifactModalService.initCreationModalModel(
                USER_ID,
                TRACKER_ID,
                PARENT_ARTIFACT_ID,
                false,
            );

            await expect(wrapPromise(promise)).resolves.toBeDefined();
            expect(getTracker).toHaveBeenCalledWith(TRACKER_ID);
            expect(tracker_transformer.transform).toHaveBeenCalledWith(tracker, true);
            const transformed_tracker = tracker_transformer.transform.mock.results[0].value;
            expect(getSelectedValues).toHaveBeenCalledWith({}, transformed_tracker);
            expect(buildFormTree).toHaveBeenCalledWith(transformed_tracker);
            const model = promise.$$state.value;
            expect(setCreationMode).toHaveBeenCalledWith(true);
            expect(model.tracker_id).toBe(TRACKER_ID);
            expect(model.parent_artifact_id).toBe(PARENT_ARTIFACT_ID);
            expect(model.tracker).toBe(transformed_tracker);
            expect(model.title).toBe("preinvest");
            expect(model.color).toBe("importer");
            expect(model.values).toBeDefined();
            expect(model.ordered_fields).toBeDefined();
            expect(model.parent_artifacts).toBeUndefined();
            expect(model.current_artifact_identifier).toBeUndefined();
            expect(model.user_id).toBe(USER_ID);
            expect(model.user_locale).toBe(USER_LOCALE);
            expect(model.user_date_time_format).toBe(USER_DATE_TIME_FORMAT);
        });

        it("Given that I could not get the tracker structure, then a promise will be rejected", async () => {
            getTracker.mockReturnValue($q.reject());

            const promise = wrapPromise(
                NewTuleapArtifactModalService.initCreationModalModel(
                    TRACKER_ID,
                    PARENT_ARTIFACT_ID,
                ),
            );

            let has_thrown = false;
            try {
                await promise;
            } catch (_e) {
                has_thrown = true;
            }
            expect(has_thrown).toBe(true);
        });

        describe("apply transitions -", () => {
            const WORKFLOW_FIELD_ID = 189;
            beforeEach(() => {
                isInCreationMode.mockReturnValue(true);
            });

            it(`Given a tracker that had workflow transitions,
                when I create the modal's creation model,
                then the transitions will be enforced`, async () => {
                const workflow_field = {
                    field_id: WORKFLOW_FIELD_ID,
                    values: [],
                };
                const workflow = {
                    is_used: "1",
                    field_id: WORKFLOW_FIELD_ID,
                    transitions: [{ from_id: null, to_id: 511 }],
                };
                tracker = {
                    id: TRACKER_ID,
                    fields: [workflow_field],
                    workflow,
                };
                getTracker.mockReturnValue($q.when(tracker));

                const promise = wrapPromise(
                    NewTuleapArtifactModalService.initCreationModalModel(TRACKER_ID),
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).toHaveBeenCalledWith(
                    null,
                    workflow_field,
                    workflow,
                );
            });

            it(`Given a tracker that had workflow transitions but were not used,
                then the transitions won't be enforced`, async () => {
                const workflow_field = {
                    field_id: WORKFLOW_FIELD_ID,
                    values: [],
                };
                const workflow = {
                    is_used: "0",
                    field_id: WORKFLOW_FIELD_ID,
                    transitions: [{ from_id: 326, to_id: 723 }],
                };
                tracker = {
                    id: TRACKER_ID,
                    fields: [workflow_field],
                    workflow,
                };
                getTracker.mockReturnValue($q.when(tracker));

                const promise = wrapPromise(
                    NewTuleapArtifactModalService.initCreationModalModel(TRACKER_ID),
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).not.toHaveBeenCalled();
            });

            it(`Given a tracker that didn't have workflow transitions,
                when I create the modal's creation model,
                then the transitions won't be enforced`, async () => {
                const workflow_field = {
                    field_id: 157,
                    values: [],
                };
                const workflow = {
                    is_used: "1",
                    field_id: WORKFLOW_FIELD_ID,
                    transitions: [],
                };
                tracker = {
                    id: TRACKER_ID,
                    fields: [workflow_field],
                    workflow,
                };
                getTracker.mockReturnValue($q.when(tracker));

                const promise = wrapPromise(
                    NewTuleapArtifactModalService.initCreationModalModel(TRACKER_ID),
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).not.toHaveBeenCalled();
            });
        });
    });

    describe("initEditionModalModel() -", () => {
        const TRACKER_ID = 93,
            ARTIFACT_ID = 250;
        let artifact_retriever;

        beforeEach(() => {
            getSelectedValues.mockReturnValue({
                113: {
                    value: "onomatomania",
                },
            });

            const comment_order_preference = {
                key: `tracker_comment_invertorder_${TRACKER_ID}`,
                value: "1",
            };

            const text_format_preference = {
                key: "user_edition_default_format",
                value: "html",
            };

            getUserPreference.mockImplementation((user_id, preference_key) => {
                if (preference_key.includes("tracker_comment_invertorder_")) {
                    return Promise.resolve(comment_order_preference);
                } else if (preference_key === "user_edition_default_format") {
                    return Promise.resolve(text_format_preference);
                } else if (preference_key === "relative_dates_display") {
                    return Promise.resolve({
                        key: "relative_dates_display",
                        value: false,
                    });
                }
                throw Error("Did not expect this preference key");
            });
        });

        const initModel = () =>
            NewTuleapArtifactModalService.initEditionModalModel(
                USER_ID,
                TRACKER_ID,
                ARTIFACT_ID,
                artifact_retriever,
            );

        describe("Create modal edition model", () => {
            const LAST_MODIFIED = "1629097552";
            beforeEach(() => {
                tracker = {
                    id: TRACKER_ID,
                    color_name: "deep-blue",
                    label: "unstainableness",
                    parent: null,
                    fields: [],
                };
                const artifact = {
                    title: "onomatomania",
                    tracker,
                    values: [{ field_id: 487, value: "unwadded" }],
                    etag: LAST_MODIFIED,
                    last_modified: LAST_MODIFIED,
                };
                artifact_retriever =
                    RetrieveCurrentArtifactWithTrackerStructureStub.withArtifact(artifact);
            });

            it(`Given a user id, tracker id and an artifact id,
                When I create the modal's edition model,
                Then the artifact's field values will be retrieved,
                    the tracker's structure will be retrieved
                    and a promise will be resolved with the modal's model object`, async () => {
                const model = await initModel();

                expect(getUserPreference).toHaveBeenCalledWith(
                    USER_ID,
                    "tracker_comment_invertorder_93",
                );
                expect(getUserPreference).toHaveBeenCalledWith(USER_ID, "relative_dates_display");
                expect(getUserPreference).toHaveBeenCalledWith(
                    USER_ID,
                    "user_edition_default_format",
                );

                expect(tracker_transformer.transform).toHaveBeenCalledWith(tracker, false);
                const transformed_tracker = tracker_transformer.transform.mock.results[0].value;
                expect(tracker_transformer.addFieldValuesToTracker).toHaveBeenCalledWith(
                    expect.any(Object),
                    transformed_tracker,
                );
                expect(getSelectedValues).toHaveBeenCalledWith(
                    expect.any(Object),
                    transformed_tracker,
                );
                expect(buildFormTree).toHaveBeenCalledWith(transformed_tracker);
                expect(model.invert_followups_comments_order).toBe(false);
                expect(model.text_fields_format).toBe("html");
                expect(model.tracker_id).toBe(TRACKER_ID);
                expect(model.current_artifact_identifier.id).toBe(ARTIFACT_ID);
                expect(model.color).toBe("deep-blue");
                expect(model.tracker).toBe(transformed_tracker);
                expect(model.values).toBeDefined();
                expect(model.ordered_fields).toBeDefined();
                expect(setCreationMode).toHaveBeenCalledWith(false);
                expect(model.title).toBe("onomatomania");
                expect(model.etag).toBe(LAST_MODIFIED);
                expect(model.last_modified).toBe(LAST_MODIFIED);
                expect(model.user_locale).toBe(USER_LOCALE);
                expect(model.user_date_time_format).toBe(USER_DATE_TIME_FORMAT);
            });

            it(`Given that the user didn't have a preference set for text fields format,
                when I create the modal's edition model,
                then the default text_field format will be "commonmark" by default`, async () => {
                const comment_order_preference = {
                    key: `tracker_comment_invertorder_${TRACKER_ID}`,
                    value: "1",
                };

                getUserPreference.mockImplementation((user_id, preference_key) => {
                    if (preference_key.includes("tracker_comment_invertorder_")) {
                        return Promise.resolve(comment_order_preference);
                    } else if (preference_key === "user_edition_default_format") {
                        return Promise.resolve({
                            key: "user_edition_default_format",
                            value: false,
                        });
                    } else if (preference_key === "relative_dates_display") {
                        return Promise.resolve({
                            key: "relative_dates_display",
                            value: false,
                        });
                    }
                    throw Error("Did not expect this preference key");
                });

                const model = await initModel();

                expect(model.text_fields_format).toBe(TEXT_FORMAT_COMMONMARK);
            });
        });

        describe("apply transitions -", () => {
            const WORKFLOW_FIELD_ID = 189;
            let workflow_field;

            beforeEach(() => {
                workflow_field = {
                    field_id: WORKFLOW_FIELD_ID,
                    values: [],
                };
            });

            it(`Given a tracker that had workflow transitions,
                when I create the modal's edition model,
                then the transitions will be enforced`, async () => {
                const BIND_VALUE_ID = 757;
                const workflow = {
                    is_used: "1",
                    field_id: WORKFLOW_FIELD_ID,
                    transitions: [{ from_id: BIND_VALUE_ID, to_id: 511 }],
                };
                const tracker = {
                    id: TRACKER_ID,
                    fields: [workflow_field],
                    workflow: workflow,
                };
                const artifact = {
                    title: "onomatomania",
                    tracker,
                    values: [{ field_id: WORKFLOW_FIELD_ID, bind_value_ids: [BIND_VALUE_ID] }],
                };
                artifact_retriever =
                    RetrieveCurrentArtifactWithTrackerStructureStub.withArtifact(artifact);

                await initModel();

                expect(enforceWorkflowTransitions).toHaveBeenCalledWith(
                    BIND_VALUE_ID,
                    workflow_field,
                    workflow,
                );
            });

            it(`Given a tracker that had workflow transitions but were not used,
                when I create the modal's edition model,
                then the transitions won't be enforced`, async () => {
                const workflow = {
                    is_used: "0",
                    field_id: WORKFLOW_FIELD_ID,
                    transitions: [
                        {
                            from_id: 757,
                            to_id: 511,
                        },
                    ],
                };
                const tracker = {
                    id: TRACKER_ID,
                    fields: [workflow_field],
                    workflow: workflow,
                };
                const artifact = {
                    title: "onomatomania",
                    tracker,
                    values: [{ field_id: 487, value: "unwadded" }],
                };
                artifact_retriever =
                    RetrieveCurrentArtifactWithTrackerStructureStub.withArtifact(artifact);

                await initModel();

                expect(enforceWorkflowTransitions).not.toHaveBeenCalled();
            });

            it(`Given a tracker that had workflow transitions on a field with missing values,
                when I create the modal's edition model,
                it does not crash and enforce the transition like in the creation`, async () => {
                const workflow = {
                    is_used: "1",
                    field_id: WORKFLOW_FIELD_ID,
                    transitions: [{ from_id: 757, to_id: 511 }],
                };
                const tracker = {
                    id: TRACKER_ID,
                    fields: [workflow_field],
                    workflow: workflow,
                };
                const artifact = {
                    title: "onomatomania",
                    tracker,
                    values: [],
                };
                artifact_retriever =
                    RetrieveCurrentArtifactWithTrackerStructureStub.withArtifact(artifact);

                await initModel();

                expect(enforceWorkflowTransitions).toHaveBeenCalledWith(
                    null,
                    workflow_field,
                    workflow,
                );
            });
        });
    });
});
