import * as tlp_fetch from "@tuleap/tlp-fetch";
import { planElementInProgramIncrement, reorderElementInProgramIncrement } from "./feature-planner";
import type { Feature } from "../../../type";
import { AFTER } from "../../feature-reordering";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("Feature planner", () => {
    describe("planElementInProgramIncrement", () => {
        it("Plan feature with order", async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);
            await planElementInProgramIncrement({
                to_program_increment_id: 4,
                feature: { id: 5 } as Feature,
                order: { direction: AFTER, compared_to: 19 },
            });

            expect(tlpPatch).toHaveBeenCalledWith(`/api/v1/program_increment/4/content`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    add: [{ id: 5 }],
                    order: { ids: [5], direction: "after", compared_to: 19 },
                }),
            });
        });
        it("Plan feature without order", async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);
            await planElementInProgramIncrement({
                to_program_increment_id: 4,
                feature: { id: 5 } as Feature,
                order: null,
            });

            expect(tlpPatch).toHaveBeenCalledWith(`/api/v1/program_increment/4/content`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    add: [{ id: 5 }],
                    order: null,
                }),
            });
        });
    });

    describe("reorderElementInProgramIncrement", () => {
        it("When formatted order is null, Then error is thrown", async () => {
            await expect(
                reorderElementInProgramIncrement({
                    to_program_increment_id: 1,
                    feature: { id: 45 } as Feature,
                    order: null,
                }),
            ).rejects.toEqual(
                new Error(
                    "Cannot reorder element #45 in program increment #1 because order is null",
                ),
            );
        });

        it("When formatted order is not null, Then request is done", async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            await reorderElementInProgramIncrement({
                to_program_increment_id: 1,
                feature: { id: 45 } as Feature,
                order: { compared_to: 9, direction: AFTER },
            });

            expect(tlpPatch).toHaveBeenCalledWith(`/api/v1/program_increment/1/content`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    add: [],
                    order: { ids: [45], direction: "after", compared_to: 9 },
                }),
            });
        });
    });
});
