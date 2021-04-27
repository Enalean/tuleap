import * as tlp from "tlp";
import { planElementInProgramIncrement, reorderElementInProgramIncrement } from "./feature-planner";
import type { Feature } from "../../../type";
import { Direction } from "../../feature-reordering";

jest.mock("tlp");

describe("Feature planner", () => {
    it("Plan elements", async () => {
        const tlpPut = jest.spyOn(tlp, "put");
        await planElementInProgramIncrement(1, 10000, [{ id: 100 }, { id: 200 }]);

        expect(tlpPut).toHaveBeenCalledWith(`/api/v1/artifacts/1`, {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                values: [{ field_id: 10000, links: [{ id: 100 }, { id: 200 }] }],
                comment: { body: "", format: "text" },
            }),
        });
    });

    describe("reorderElementInProgramIncrement", () => {
        it("When formatted order is null, Then error is thrown", async () => {
            await expect(
                reorderElementInProgramIncrement({
                    program_increment_id: 1,
                    feature: { id: 45 } as Feature,
                    order: null,
                })
            ).rejects.toEqual(
                new Error(
                    "Cannot reorder element #45 in program increment #1 because order is null"
                )
            );
        });

        it("When formatted order is not null, Then request is done", async () => {
            const tlpPatch = jest.spyOn(tlp, "patch");

            await reorderElementInProgramIncrement({
                program_increment_id: 1,
                feature: { id: 45 } as Feature,
                order: { compared_to: 9, direction: Direction.AFTER },
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
