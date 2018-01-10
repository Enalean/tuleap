import { canChooseArtifactsParent } from './link-field-service.js';
import {
    rewire$isInCreationMode,
    restore
} from '../../modal-creation-mode-state.js';

describe("TuleapArtifactModalParentService -", () => {
    let isInCreationMode;

    beforeEach(() => {
        isInCreationMode = jasmine.createSpy("isInCreationMode").and.returnValue(true);
        rewire$isInCreationMode(isInCreationMode);
    });

    afterEach(() => {
        restore();
    });

    describe("canChooseArtifactsParent() -", () => {
        it("Given that the modal was opened in edition mode, then it will return false", () => {
            isInCreationMode.and.returnValue(false);
            const tracker         = { id: 9, parent: { id: 32 } };
            const linked_artifact = {
                artifact: {
                    tracker: { id: 24 }
                }
            };

            const result = canChooseArtifactsParent(tracker, linked_artifact);

            expect(result).toBe(false);
        });

        it("Given no parent tracker, then it will return false", () => {
            const tracker         = { id: 82 };
            const linked_artifact = {
                artifact: { id: 38 }
            };
            const result = canChooseArtifactsParent(tracker, linked_artifact);

            expect(result).toBe(false);
        });

        it("Given a parent tracker and no linked_artifact, then it will return true", function() {
            const tracker = { id: 33, parent: { id: 86 } };

            const result = canChooseArtifactsParent(tracker, null);

            expect(result).toBe(true);
        });

        it("Given no parent tracker and no linked_artifact, then it will return false", () => {
            const tracker = { id: 30 };

            const result = canChooseArtifactsParent(tracker, null);

            expect(result).toBe(false);
        });

        it("Given a parent tracker and a linked_artifact and given that the linked_artifact's tracker id is different from the parent tracker's id, then it will return true", function() {
            const tracker         = { id: 60, parent: { id: 66 } };
            const linked_artifact = {
                artifact: {
                    tracker: { id: 95 }
                }
            };

            const result = canChooseArtifactsParent(tracker, linked_artifact);

            expect(result).toBe(true);
        });

        it("Given a parent tracker and a linked_artifact and given that the linked_artifact's tracker id is the same as the parent_tracker's id, then it will return false", function() {
            const tracker         = { id: 13, parent: { id: 20 } };
            const linked_artifact = {
                artifact: {
                    tracker: {
                        id: 20
                    }
                }
            };

            const result = canChooseArtifactsParent(tracker, linked_artifact);

            expect(result).toBe(false);
        });

        it("Given a parent tracker that is malformed, then it will return false", function() {
            const tracker         = { cityfolk: 44 };
            const linked_artifact = {
                artifact: {
                    tracker: {
                        id: 79
                    }
                }
            };

            const result = canChooseArtifactsParent(tracker, linked_artifact);

            expect(result).toBe(false);
        });

        it("Given a parent tracker and a linked_artifact that is malformed, then it will return false ", function() {
            const tracker         = { id: 22 };
            const linked_artifact = {
                artifact: {
                    isogeny: {
                        goblinesque: 99
                    }
                }
            };

            const result = canChooseArtifactsParent(tracker, linked_artifact);

            expect(result).toBe(false);
        });
    });
});
