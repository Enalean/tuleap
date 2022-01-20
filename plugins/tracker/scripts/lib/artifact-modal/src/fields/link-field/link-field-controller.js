import { createListPicker } from "@tuleap/list-picker";
import { canChooseArtifactsParent } from "./link-field-service.js";
import { isInCreationMode } from "../../modal-creation-mode-state.js";
import {
    getArtifact,
    getAllOpenParentArtifacts,
    getFirstReverseIsChildLink,
} from "../../rest/rest-service";

export default LinkFieldController;

LinkFieldController.$inject = ["$q", "$element", "$scope", "gettextCatalog"];

function LinkFieldController($q, $element, $scope, gettextCatalog) {
    const self = this;

    Object.assign(self, {
        $onInit: init,
        $onDestroy: destroy,
        showParentArtifactChoice,
        loadParentArtifactsTitle,
        hasArtifactAlreadyAParent,
        is_loading: false,
        parent_artifact: null,
        possible_parent_artifacts: [],
        destroy_list_picker_callback: () => {},
    });

    function init() {
        self.is_loading = true;
        getParentArtifact()
            .then((linked_artifact) => {
                self.parent_artifact = linked_artifact;

                const canChoose = canChooseArtifactsParent(
                    self.tracker,
                    self.parent_artifact,
                    self.has_current_project_parents
                );
                if (canChoose === true) {
                    return self.loadParentArtifactsTitle();
                }
            })
            .finally(() => {
                self.is_loading = false;
            });

        watchParentSelector();
    }

    function watchParentSelector() {
        if (!self.is_list_picker_enabled) {
            return;
        }

        $scope.$watch(hasSelectBeenRendered, (is_select_available) => {
            if (!is_select_available) {
                return;
            }

            initListPicker();
        });
    }

    function hasSelectBeenRendered() {
        return $element[0].querySelector("[data-select-type=list-picker]") !== null;
    }

    async function initListPicker() {
        const select = $element[0].querySelector("[data-select-type=list-picker]");
        const options = {
            locale: document.body.dataset.userLocale,
            is_filterable: true,
            placeholder: gettextCatalog.getString("Please choose a parent…"),
        };

        self.destroy_list_picker_callback = await createListPicker(select, options).then(
            (list_picker) => {
                return list_picker.destroy;
            }
        );
    }

    function destroy() {
        self.destroy_list_picker_callback();
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
        const canChoose = canChooseArtifactsParent(
            self.tracker,
            self.parent_artifact,
            self.has_current_project_parents
        );
        return canChoose && self.possible_parent_artifacts.length > 0;
    }

    function loadParentArtifactsTitle() {
        return $q
            .when(getAllOpenParentArtifacts(self.tracker.id, 1000, 0))
            .then(buildCategorisedListOfPossibleParentArtifacts)
            .catch(() => {
                // No trackers in parents project from which to select artifacts
                return $q.when([]);
            });
    }

    function buildCategorisedListOfPossibleParentArtifacts(artifacts) {
        const categories = artifacts.reduce((arts_by_projects_trackers, current_artifact) => {
            const tracker = current_artifact.tracker;

            if (arts_by_projects_trackers.has(tracker.id)) {
                arts_by_projects_trackers.get(tracker.id).artifacts.push({
                    id: current_artifact.id,
                    formatted_ref: formatArtifact(current_artifact),
                });
            } else {
                arts_by_projects_trackers.set(tracker.id, {
                    label: gettextCatalog.getString(
                        "{{ project_label }} - open {{ tracker_label }}",
                        {
                            project_label: current_artifact.tracker.project.label,
                            tracker_label: tracker.label,
                        }
                    ),
                    artifacts: [
                        {
                            id: current_artifact.id,
                            formatted_ref: formatArtifact(current_artifact),
                        },
                    ],
                });
            }

            return arts_by_projects_trackers;
        }, new Map());

        self.possible_parent_artifacts = Array.from(categories.values());
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
