/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { useSectionEditor } from "@/composables/useSectionEditor";
import * as rest_querier from "@/helpers/rest-querier";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { errAsync, okAsync } from "neverthrow";
import { flushPromises } from "@vue/test-utils";
import * as on_before_unload from "@/helpers/on-before-unload";
import { TuleapAPIFaultStub } from "@/helpers/stubs/TuleapAPIFaultStub";
import type {
    ArtidocSection,
    ArtifactSection,
    PendingArtifactSection,
} from "@/helpers/artidoc-section.type";
import * as latest from "@/helpers/is-section-in-its-latest-version";
import { OutdatedSectionFault } from "@/helpers/is-section-in-its-latest-version";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { PositionForSave } from "@/stores/useSectionsStore";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { Fault } from "@tuleap/fault";

const default_section = ArtifactSectionFactory.create();

const section = ArtifactSectionFactory.override({
    artifact: {
        ...default_section.artifact,
        id: 1,
    },
    description: {
        ...default_section.description,
        value: "the original description",
        post_processed_value: "the description",
    },
});

describe("useSectionEditor", () => {
    let update_section_callback: (section: ArtifactSection) => void;
    let remove_section_callback: (section: ArtidocSection) => void;
    let get_section_position_callback: (section: ArtidocSection) => PositionForSave;
    let replace_pending_by_artifact_section_callback: (
        pending: PendingArtifactSection,
        section: ArtifactSection,
    ) => void;

    beforeEach(() => {
        update_section_callback = vi.fn();
        remove_section_callback = vi.fn();
        get_section_position_callback = vi.fn();
        replace_pending_by_artifact_section_callback = vi.fn();
    });

    describe("getReadonlyDescription", () => {
        it("should return the post processed value", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            expect(store.getReadonlyDescription().value).toBe("the description");
        });
    });

    describe("getEditableDescription", () => {
        it("should return the value when format is html", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                {
                    ...section,
                    description: {
                        ...default_section.description,
                        value: "<p>the original description see art #1</p>",
                        format: "html",
                        post_processed_value:
                            "<p>the original description see <a href=''>art #1</a></p>",
                    },
                },
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );

            expect(store.getEditableDescription().value).toBe(
                "<p>the original description see art #1</p>",
            );
        });

        it("should return the value converted as html when format is text", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                {
                    ...section,
                    description: {
                        ...default_section.description,
                        value: "the original description see art #1",
                        format: "text",
                        post_processed_value:
                            "<p>the original description see <a href=''>art #1</a></p>",
                    },
                },
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );

            expect(store.getEditableDescription().value).toBe(
                "<p>the original description see art #1</p>\n",
            );
        });

        it("should return the value converted as html when format is markdown", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                {
                    ...section,
                    description: {
                        ...default_section.description,
                        value: "<p>the original description see <a href=''>art #1</a></p>",
                        format: "html",
                        commonmark: "the original description see art #1",
                        post_processed_value:
                            "<p>the original description see <a href=''>art #1</a></p>",
                    },
                },
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );

            expect(store.getEditableDescription().value).toBe(
                "<p>the original description see art #1</p>\n",
            );
        });
    });

    describe("inputCurrentDescription", () => {
        it("should input current description", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            expect(store.getEditableDescription().value).toBe("the original description");

            store.inputCurrentDescription("new description");

            expect(store.getEditableDescription().value).toBe("new description");
        });
    });

    describe("enableEditor", () => {
        it("should enable edit mode", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            expect(store.isSectionInEditMode().value).toBe(false);

            store.editor_actions.enableEditor();

            expect(store.isSectionInEditMode().value).toBe(true);
        });
    });

    describe("cancelEditor", () => {
        it("should cancel edit mode", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            store.editor_actions.enableEditor();
            expect(store.isSectionInEditMode().value).toBe(true);
            store.inputCurrentDescription("the description changed");
            expect(store.getEditableDescription().value).toBe("the description changed");

            store.editor_actions.cancelEditor(null);

            expect(store.isSectionInEditMode().value).toBe(false);
            expect(store.getEditableDescription().value).toBe("the original description");
            expect(remove_section_callback).not.toHaveBeenCalled();
        });

        it("should remove the section if it is a pending one", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                PendingArtifactSectionFactory.create(),
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );

            store.editor_actions.cancelEditor(null);

            expect(remove_section_callback).toHaveBeenCalled();
        });
    });

    describe("saveEditor", () => {
        describe("when the description is the same as the original description", () => {
            it("should not put artifact description", () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                const store = useSectionEditor(
                    section,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );
                const mock_put_artifact_description = vi.spyOn(rest_querier, "putArtifact");

                store.editor_actions.saveEditor();

                expect(mock_put_artifact_description).not.toHaveBeenCalled();
                expect(store.isBeeingSaved().value).toBe(false);
            });
        });

        describe("when the description is different from the original description", () => {
            it("should end in error in case of 400", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                vi.spyOn(latest, "isSectionInItsLatestVersion").mockReturnValue(okAsync(true));

                const store = useSectionEditor(
                    section,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );
                const mock_put_artifact_description = vi
                    .spyOn(rest_querier, "putArtifact")
                    .mockReturnValue(
                        errAsync(TuleapAPIFaultStub.fromCodeAndMessage(400, "An error occurred.")),
                    );

                store.inputCurrentDescription("new description");
                expect(store.getEditableDescription().value).toBe("new description");
                expect(store.isInError().value).toBe(false);
                expect(store.getErrorMessage().value).toBe("");

                store.editor_actions.saveEditor();

                await flushPromises();

                expect(mock_put_artifact_description).toHaveBeenCalledOnce();

                expect(store.isInError().value).toBe(true);
                expect(store.getErrorMessage().value).toBe("An error occurred.");
            });

            it("should end in NotFound error in case of 404", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                vi.spyOn(latest, "isSectionInItsLatestVersion").mockReturnValue(okAsync(true));

                const store = useSectionEditor(
                    section,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );
                const mock_put_artifact_description = vi
                    .spyOn(rest_querier, "putArtifact")
                    .mockReturnValue(
                        errAsync(TuleapAPIFaultStub.fromCodeAndMessage(404, "Not found")),
                    );

                store.inputCurrentDescription("new description");
                expect(store.getEditableDescription().value).toBe("new description");
                expect(store.isInError().value).toBe(false);
                expect(store.isNotFoundError().value).toBe(false);

                store.editor_actions.saveEditor();

                await flushPromises();

                expect(mock_put_artifact_description).toHaveBeenCalledOnce();

                expect(store.isInError().value).toBe(true);
                expect(store.isNotFoundError().value).toBe(true);
            });

            it("should end in NotFound error in case of 403", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                vi.spyOn(latest, "isSectionInItsLatestVersion").mockReturnValue(okAsync(true));

                const store = useSectionEditor(
                    section,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );
                const mock_put_artifact_description = vi
                    .spyOn(rest_querier, "putArtifact")
                    .mockReturnValue(
                        errAsync(TuleapAPIFaultStub.fromCodeAndMessage(403, "Forbidden")),
                    );

                store.inputCurrentDescription("new description");
                expect(store.getEditableDescription().value).toBe("new description");
                expect(store.isInError().value).toBe(false);
                expect(store.isNotFoundError().value).toBe(false);

                store.editor_actions.saveEditor();

                await flushPromises();

                expect(mock_put_artifact_description).toHaveBeenCalledOnce();

                expect(store.isInError().value).toBe(true);
                expect(store.isNotFoundError().value).toBe(true);
            });

            it("should not perform the update if the section is outdated", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                vi.spyOn(latest, "isSectionInItsLatestVersion").mockReturnValue(
                    errAsync(OutdatedSectionFault.build()),
                );

                const store = useSectionEditor(
                    section,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );
                const mock_put_artifact_description = vi.spyOn(rest_querier, "putArtifact");

                store.inputCurrentDescription("new description");
                expect(store.getEditableDescription().value).toBe("new description");
                expect(store.getReadonlyDescription().value).toBe("the description");

                expect(store.isOutdated().value).toBe(false);

                store.editor_actions.saveEditor();

                await flushPromises();

                expect(mock_put_artifact_description).not.toHaveBeenCalled();
                expect(store.isOutdated().value).toBe(true);
            });

            it("should get updated section", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                vi.spyOn(latest, "isSectionInItsLatestVersion").mockReturnValue(okAsync(true));

                const store = useSectionEditor(
                    section,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );
                const mock_put_artifact_description = vi
                    .spyOn(rest_querier, "putArtifact")
                    .mockReturnValue(okAsync(new Response()));
                const mock_get_section = vi.spyOn(rest_querier, "getSection").mockReturnValue(
                    okAsync(
                        ArtifactSectionFactory.override({
                            description: {
                                ...default_section.description,
                                value: "the original description",
                                post_processed_value: "the updated post_processed_value",
                            },
                        }),
                    ),
                );

                store.inputCurrentDescription("new description");
                expect(store.getEditableDescription().value).toBe("new description");
                expect(store.getReadonlyDescription().value).toBe("the description");

                store.editor_actions.saveEditor();

                await flushPromises();

                expect(mock_put_artifact_description).toHaveBeenCalled();
                expect(mock_get_section).toHaveBeenCalled();
                expect(store.getReadonlyDescription().value).toBe(
                    "the updated post_processed_value",
                );
                expect(update_section_callback).toHaveBeenCalled();
            });
        });

        describe("when the section is a pending artifact section", () => {
            it("should create an artifact, create a section, and replace the pending section by the new artifact section", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                const pending = PendingArtifactSectionFactory.create();
                const store = useSectionEditor(
                    pending,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );

                const mock_post_artifact = vi
                    .spyOn(rest_querier, "postArtifact")
                    .mockReturnValue(okAsync({ id: 123 }));

                const section = ArtifactSectionFactory.create();
                const mock_create_section = vi
                    .spyOn(rest_querier, "createSection")
                    .mockReturnValue(okAsync(section));

                store.inputCurrentDescription("new description");

                store.editor_actions.saveEditor();
                await flushPromises();

                expect(mock_post_artifact).toHaveBeenCalled();
                expect(mock_create_section).toHaveBeenCalled();
                expect(get_section_position_callback).toHaveBeenCalled();
                expect(replace_pending_by_artifact_section_callback).toHaveBeenCalledWith(
                    pending,
                    section,
                );
            });

            it("should display error when creating the artifact", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                const pending = PendingArtifactSectionFactory.create();
                const store = useSectionEditor(
                    pending,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );

                const mock_post_artifact = vi
                    .spyOn(rest_querier, "postArtifact")
                    .mockReturnValue(errAsync(Fault.fromMessage("Bad request")));

                const mock_create_section = vi.spyOn(rest_querier, "createSection");

                store.inputCurrentDescription("new description");

                store.editor_actions.saveEditor();
                await flushPromises();

                expect(mock_post_artifact).toHaveBeenCalled();
                expect(mock_create_section).not.toHaveBeenCalled();
                expect(get_section_position_callback).not.toHaveBeenCalled();
                expect(replace_pending_by_artifact_section_callback).not.toHaveBeenCalled();

                expect(store.isInError().value).toBe(true);
                expect(store.getErrorMessage().value).toBe("Bad request");
            });

            it("should display error when creating the section", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                const pending = PendingArtifactSectionFactory.create();
                const store = useSectionEditor(
                    pending,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );

                const mock_post_artifact = vi
                    .spyOn(rest_querier, "postArtifact")
                    .mockReturnValue(okAsync({ id: 123 }));

                const mock_create_section = vi
                    .spyOn(rest_querier, "createSection")
                    .mockReturnValue(errAsync(Fault.fromMessage("Bad request")));

                store.inputCurrentDescription("new description");

                store.editor_actions.saveEditor();
                await flushPromises();

                expect(mock_post_artifact).toHaveBeenCalled();
                expect(mock_create_section).toHaveBeenCalled();
                expect(get_section_position_callback).toHaveBeenCalled();
                expect(replace_pending_by_artifact_section_callback).not.toHaveBeenCalled();

                expect(store.isInError().value).toBe(true);
                expect(store.getErrorMessage().value).toBe("Bad request");
            });
        });
    });

    describe("forceSaveEditor", () => {
        it("should end in error in case of... error", async () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            const mock_put_artifact_description = vi
                .spyOn(rest_querier, "putArtifact")
                .mockReturnValue(
                    errAsync(TuleapAPIFaultStub.fromCodeAndMessage(400, "An error occurred.")),
                );

            store.inputCurrentDescription("new description");
            expect(store.getEditableDescription().value).toBe("new description");
            expect(store.isInError().value).toBe(false);

            store.editor_actions.forceSaveEditor();

            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalledOnce();

            expect(store.isInError().value).toBe(true);
        });

        it("should get updated section", async () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            const mock_put_artifact_description = vi
                .spyOn(rest_querier, "putArtifact")
                .mockReturnValue(okAsync(new Response()));
            const mock_get_section = vi.spyOn(rest_querier, "getSection").mockReturnValue(
                okAsync(
                    ArtifactSectionFactory.override({
                        description: {
                            ...default_section.description,
                            value: "the original description",
                            post_processed_value: "the updated post_processed_value",
                        },
                    }),
                ),
            );

            store.inputCurrentDescription("new description");
            expect(store.getEditableDescription().value).toBe("new description");
            expect(store.getReadonlyDescription().value).toBe("the description");

            store.editor_actions.forceSaveEditor();

            await flushPromises();

            expect(mock_put_artifact_description).toHaveBeenCalled();
            expect(mock_get_section).toHaveBeenCalled();
            expect(store.getReadonlyDescription().value).toBe("the updated post_processed_value");
            expect(update_section_callback).toHaveBeenCalled();
        });

        describe("when the section is a pending artifact section", () => {
            it("should do not save anything because the feature is not implemented yet", async () => {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, true],
                    [DOCUMENT_ID, 1],
                ]);

                const store = useSectionEditor(
                    PendingArtifactSectionFactory.create(),
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );

                const mock_put_artifact_description = vi.spyOn(rest_querier, "putArtifact");

                store.editor_actions.forceSaveEditor();
                await flushPromises();

                expect(mock_put_artifact_description).not.toHaveBeenCalled();
            });
        });
    });

    describe("refreshSection", () => {
        it("should refresh the section", async () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            vi.spyOn(rest_querier, "getSection").mockReturnValue(
                okAsync(
                    ArtifactSectionFactory.override({
                        display_title: "concurrently edited title",
                        description: {
                            ...section.description,
                            value: "concurrently edited description",
                            post_processed_value: "concurrently edited description",
                        },
                    }),
                ),
            );

            store.editor_actions.enableEditor();

            store.inputCurrentDescription("new description");
            expect(store.getEditableDescription().value).toBe("new description");
            expect(store.getReadonlyDescription().value).toBe("the description");

            store.inputCurrentTitle("new title");
            expect(store.getEditableTitle().value).toBe("new title");
            store.isInError().value = true;

            store.editor_actions.refreshSection();

            await flushPromises();

            expect(store.isSectionInEditMode().value).toBe(false);
            expect(store.getEditableDescription().value).toBe("concurrently edited description");
            expect(store.getReadonlyDescription().value).toBe("concurrently edited description");
            expect(store.getEditableTitle().value).toBe("concurrently edited title");
            expect(store.isInError().value).toBe(false);
        });
    });

    describe("is_section_editable", () => {
        it.each([
            [false, false, false],
            [false, true, false],
            [true, false, false],
            [true, true, true],
        ])(
            `When user can edit document = %s
            And can edit section = %s
            Then the is_section_editable = %s`,
            function (can_user_edit_document, can_user_edit_section, expected) {
                mockStrictInject([
                    [CAN_USER_EDIT_DOCUMENT, can_user_edit_document],
                    [DOCUMENT_ID, 1],
                ]);

                const artifact_section: ArtifactSection = { ...section, can_user_edit_section };

                const store = useSectionEditor(
                    artifact_section,
                    update_section_callback,
                    remove_section_callback,
                    get_section_position_callback,
                    replace_pending_by_artifact_section_callback,
                );

                expect(store.is_section_editable.value).toBe(expected);
            },
        );
    });

    describe("page leave", () => {
        it("should prevent page leave when section is in edit mode", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const preventPageLeave = vi.spyOn(on_before_unload, "preventPageLeave");

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            store.clearGlobalNumberOfOpenEditorForTests();
            expect(store.isSectionInEditMode().value).toBe(false);

            store.editor_actions.enableEditor();

            expect(preventPageLeave).toHaveBeenCalled();
        });

        it("should allow page leave when section is not anymore in edit mode", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const allowPageLeave = vi.spyOn(on_before_unload, "allowPageLeave");

            const store = useSectionEditor(
                section,
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            store.clearGlobalNumberOfOpenEditorForTests();
            expect(store.isSectionInEditMode().value).toBe(false);

            store.editor_actions.enableEditor();
            store.editor_actions.cancelEditor(null);

            expect(allowPageLeave).toHaveBeenCalled();
        });

        it("should still prevent page leave when at least one section is in edit mode", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [DOCUMENT_ID, 1],
            ]);

            const allowPageLeave = vi.spyOn(on_before_unload, "allowPageLeave");

            const first_store = useSectionEditor(
                ArtifactSectionFactory.override({
                    artifact: {
                        ...default_section.artifact,
                        id: 1,
                    },
                }),
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );
            first_store.clearGlobalNumberOfOpenEditorForTests();
            const second_store = useSectionEditor(
                ArtifactSectionFactory.override({
                    artifact: {
                        ...default_section.artifact,
                        id: 2,
                    },
                }),
                update_section_callback,
                remove_section_callback,
                get_section_position_callback,
                replace_pending_by_artifact_section_callback,
            );

            expect(first_store.isSectionInEditMode().value).toBe(false);
            expect(second_store.isSectionInEditMode().value).toBe(false);

            first_store.editor_actions.enableEditor();
            second_store.editor_actions.enableEditor();
            expect(first_store.isSectionInEditMode().value).toBe(true);
            expect(second_store.isSectionInEditMode().value).toBe(true);

            first_store.editor_actions.cancelEditor(null);
            expect(first_store.isSectionInEditMode().value).toBe(false);
            expect(second_store.isSectionInEditMode().value).toBe(true);

            expect(allowPageLeave).not.toHaveBeenCalled();
        });
    });
});
