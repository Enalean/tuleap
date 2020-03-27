import { canChooseArtifactsParent } from "./link-field-service.js";
import { isInCreationMode } from "../../modal-creation-mode-state.js";
import {
    getArtifact,
    getAllOpenParentArtifacts,
    getFirstReverseIsChildLink,
} from "../../rest/rest-service.js";

export default LinkFieldController;

LinkFieldController.$inject = ["$q"];

function LinkFieldController($q) {
    const self = this;

    Object.assign(self, {
        $onInit: init,
        showParentArtifactChoice,
        loadParentArtifactsTitle,
        hasArtifactAlreadyAParent,
        is_loading: false,
        parent_artifact: null,
        possible_parent_artifacts: [],
    });

    function init() {
        self.is_loading = true;
        getParentArtifact()
            .then((linked_artifact) => {
                self.parent_artifact = linked_artifact;

                const canChoose = canChooseArtifactsParent(self.tracker, self.parent_artifact);
                if (canChoose === true) {
                    return self.loadParentArtifactsTitle();
                }
            })
            .finally(() => {
                self.is_loading = false;
            });
    }

    function getParentArtifact() {
        if (!isInCreationMode()) {
            return self.hasArtifactAlreadyAParent();
        }

        if (self.parent_artifact_id) {
            return $q.when(getArtifact(self.parent_artifact_id));
        }

        return $q.when(null);
    }

    function showParentArtifactChoice() {
        const canChoose = canChooseArtifactsParent(self.tracker, self.parent_artifact);
        return canChoose && self.possible_parent_artifacts.length > 0;
    }

    function loadParentArtifactsTitle() {
        return $q.when(getAllOpenParentArtifacts(self.tracker.id, 1000, 0)).then((artifacts) => {
            self.possible_parent_artifacts = artifacts.map((artifact) => {
                return {
                    id: artifact.id,
                    formatted_ref: formatArtifact(artifact),
                };
            });
        });
    }

    function hasArtifactAlreadyAParent() {
        return $q.when(getFirstReverseIsChildLink(self.artifact_id)).then((parent_artifacts) => {
            if (parent_artifacts.length > 0) {
                return parent_artifacts[0];
            }

            return null;
        });
    }

    function formatArtifact(artifact) {
        const tracker_label = getTrackerLabel(artifact);
        return `${tracker_label} #${artifact.id} - ${artifact.title}`;
    }

    function getTrackerLabel(artifact) {
        if ("tracker" in artifact && "label" in artifact.tracker) {
            return artifact.tracker.label;
        }

        return "";
    }
}
