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

import { describe, expect, it, vi } from "vitest";
import useSectionEditor from "@/composables/useSectionEditor";
import * as rest_querier from "@/helpers/rest-querier";

const default_description = {
    field_id: 1,
    type: "",
    label: "Original Submission",
    value: "the original description",
    format: "html",
    post_processed_value: "the description",
};
const default_artifact_id = 1;
describe("useSectionEditor", () => {
    describe("inputCurrentDescription", () => {
        it("should input current description", () => {
            const store = useSectionEditor(default_description, default_artifact_id);
            expect(store.getEditableDescription().value).toBe("the original description");

            store.inputCurrentDescription("new description");

            expect(store.getEditableDescription().value).toBe("new description");
        });
    });
    describe("setEditMode", () => {
        it("should enable edit mode", () => {
            const store = useSectionEditor(default_description, default_artifact_id);
            expect(store.getIsEditMode().value).toBe(false);

            store.editor_actions.setEditMode(true);

            expect(store.getIsEditMode().value).toBe(true);
        });
    });
    describe("cancelEditor", () => {
        it("should cancel edit mode", () => {
            const store = useSectionEditor(default_description, default_artifact_id);
            store.editor_actions.setEditMode(true);
            expect(store.getIsEditMode().value).toBe(true);
            store.inputCurrentDescription("the description changed");
            expect(store.getEditableDescription().value).toBe("the description changed");

            store.editor_actions.cancelEditor();

            expect(store.getIsEditMode().value).toBe(false);
            expect(store.getEditableDescription().value).toBe("the original description");
        });
    });
    describe("saveEditor", () => {
        describe("when the description is the same as the original description", () => {
            it("should not put artifact description", () => {
                const store = useSectionEditor(default_description, default_artifact_id);
                const mock_put_artifact_description = vi.spyOn(
                    rest_querier,
                    "putArtifactDescription",
                );

                store.editor_actions.saveEditor();

                expect(mock_put_artifact_description).not.toHaveBeenCalled();
            });
        });
        describe("when the description is different from the original description", () => {
            it("should put artifact description", () => {
                const store = useSectionEditor(default_description, default_artifact_id);
                const mock_put_artifact_description = vi.spyOn(
                    rest_querier,
                    "putArtifactDescription",
                );
                store.inputCurrentDescription("new description");
                expect(store.getEditableDescription().value).toBe("new description");

                store.editor_actions.saveEditor();

                expect(mock_put_artifact_description).toHaveBeenCalledOnce();
            });
        });
    });
});
