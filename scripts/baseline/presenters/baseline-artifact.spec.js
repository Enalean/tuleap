import { create } from "../support/factories";
import { presentLinkedArtifactsAsGraph } from "../presenters/baseline-artifact";
import { ARTIFACTS_EXPLORATION_DEPTH_LIMIT } from "../constants";
import ArrayUtils from "../support/array-utils";
import GraphUtils from "../support/graph-utils";

describe("presentLinkedArtifactsAsGraph()", () => {
    let first_level_artifacts;
    let all_artifacts;
    let presented_linked_artifacts;

    const mapId = artifacts => ArrayUtils.mapAttribute(artifacts, "id");

    const filterPresentedLinkedArtifacts = (presented_linked_artifacts, predicate) => {
        return GraphUtils.findAllNodes(presented_linked_artifacts, "linked_artifacts", predicate);
    };

    beforeEach(() => {
        const artifact = create("baseline_artifact", { id: 1, linked_artifact_ids: [2] });
        first_level_artifacts = [artifact];

        all_artifacts = [artifact, create("baseline_artifact", { id: 2, linked_artifact_ids: [] })];

        presented_linked_artifacts = presentLinkedArtifactsAsGraph(
            first_level_artifacts,
            all_artifacts
        );
    });

    it("returns presented artifacts tree", () => {
        expect(mapId(presented_linked_artifacts[0].linked_artifacts)).toEqual([2]);
    });

    it("makes copy of artifacts", () => {
        expect(presented_linked_artifacts[0].linked_artifacts[0]).not.toBe(
            first_level_artifacts[0]
        );
    });

    it("returns artifacts that has not reached the depth limit", () => {
        const loaded_artifacts_count = filterPresentedLinkedArtifacts(
            presented_linked_artifacts,
            artifact => artifact.is_depth_limit_reached === false
        ).length;

        expect(loaded_artifacts_count).toEqual(2);
    });

    it("does not return artifacts that has reached the depth limit", () => {
        const loaded_artifacts_count = filterPresentedLinkedArtifacts(
            presented_linked_artifacts,
            artifact => artifact.is_depth_limit_reached === true
        ).length;
        expect(loaded_artifacts_count).toEqual(0);
    });

    describe("when artifacts have grandchild", () => {
        beforeEach(() => {
            first_level_artifacts = [
                create("baseline_artifact", { id: 1, linked_artifact_ids: [2] })
            ];

            all_artifacts = [
                create("baseline_artifact", { id: 1, linked_artifact_ids: [2] }),
                create("baseline_artifact", { id: 2, linked_artifact_ids: [3] }),
                create("baseline_artifact", { id: 3, linked_artifact_ids: [] })
            ];

            presented_linked_artifacts = presentLinkedArtifactsAsGraph(
                first_level_artifacts,
                all_artifacts
            );
        });

        it("returns presented artifacts tree", () => {
            expect(mapId(presented_linked_artifacts[0].linked_artifacts)).toEqual([2]);
            expect(
                mapId(presented_linked_artifacts[0].linked_artifacts[0].linked_artifacts)
            ).toEqual([3]);
        });

        it("returns artifacts that has not reached the depth limit", () => {
            const loaded_artifacts_count = filterPresentedLinkedArtifacts(
                presented_linked_artifacts,
                artifact => artifact.is_depth_limit_reached === false
            ).length;

            expect(loaded_artifacts_count).toEqual(3);
        });

        it("does not return artifacts that has not reached the depth limit", () => {
            const loaded_artifacts_count = filterPresentedLinkedArtifacts(
                presented_linked_artifacts,
                artifact => artifact.is_depth_limit_reached === true
            ).length;
            expect(loaded_artifacts_count).toEqual(0);
        });
    });

    describe("when there is a cyclic dependency between two artifacts", () => {
        beforeEach(() => {
            first_level_artifacts = [
                create("baseline_artifact", { id: 1, linked_artifact_ids: [1] })
            ];

            all_artifacts = [create("baseline_artifact", { id: 1, linked_artifact_ids: [1] })];
            presented_linked_artifacts = presentLinkedArtifactsAsGraph(
                first_level_artifacts,
                all_artifacts
            );
        });

        it("returns one artifact that has reached the depth limit", () => {
            const unloaded_artifacts = filterPresentedLinkedArtifacts(
                presented_linked_artifacts,
                artifact => artifact.is_depth_limit_reached === true
            ).length;
            expect(unloaded_artifacts).toEqual(1);
        });

        it("returns artifacts that has not reached the depth limit", () => {
            const loaded_artifacts = filterPresentedLinkedArtifacts(
                presented_linked_artifacts,
                artifact => artifact.is_depth_limit_reached === false
            ).length;
            expect(loaded_artifacts).toEqual(ARTIFACTS_EXPLORATION_DEPTH_LIMIT - 1);
        });
    });
});
