import { create } from "@tuleap/core/src/labels/labels-box.js";

export default LabelsController;

LabelsController.$inject = ["$element", "SharedPropertiesService"];

function LabelsController($element, SharedPropertiesService) {
    const self = this;
    self.$onInit = initLabels;

    function initLabels() {
        SharedPropertiesService.whenReady().then(function () {
            const pull_request = SharedPropertiesService.getPullRequest();

            createLabelsBox(
                pull_request.id,
                pull_request.repository_dest.project.id,
                pull_request.user_can_update_labels
            );
        });
    }

    function createLabelsBox(pull_request_id, project_id, user_can_update_labels) {
        create(
            $element[0].children[0],
            "/api/v1/pull_requests/" + pull_request_id + "/labels",
            "/api/v1/projects/" + project_id + "/labels",
            user_can_update_labels,
            self.placeholder
        );
    }
}
