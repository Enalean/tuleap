import * as tlp from "tlp";
import { planElementInProgramIncrement } from "./feature-planner";

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
});
