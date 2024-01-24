import {
    BLOCKED_STATUS,
    FAILED_STATUS,
    NOT_RUN_STATUS,
    PASSED_STATUS,
} from "../../execution-constants.js";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { resetError, setError } from "../../../feedback-state.js";
import { updateStatusWithStepResults, updateStepResults } from "./execution-with-steps-updater.js";
import { sanitize } from "dompurify";

controller.$inject = [
    "$sce",
    "$element",
    "gettextCatalog",
    "ExecutionRestService",
    "ExecutionService",
];

export default function controller(
    $sce,
    $element,
    gettextCatalog,
    ExecutionRestService,
    ExecutionService,
) {
    const self = this;
    Object.assign(self, {
        saving: false,
        setToPassed() {
            setNewStatusIfNotSaving(PASSED_STATUS);
        },
        setToFailed() {
            setNewStatusIfNotSaving(FAILED_STATUS);
        },
        setToBlocked() {
            setNewStatusIfNotSaving(BLOCKED_STATUS);
        },
        setToNotRun() {
            setNewStatusIfNotSaving(NOT_RUN_STATUS);
        },
        isPassed: () => self.step_result.status === PASSED_STATUS,
        isFailed: () => self.step_result.status === FAILED_STATUS,
        isBlocked: () => self.step_result.status === BLOCKED_STATUS,
        isNotRun: () => self.step_result.status === NOT_RUN_STATUS,
        openDropdown: () => self.dropdown.show(),
        $onInit: init,
        sanitizedContentWithEnhancedCodeBlocks,
    });

    function init() {
        self.step_result = self.step_result
            ? self.step_result
            : {
                  status: "notrun",
              };
        const $trigger = $element.find(".steps-step-action-dropdown-trigger");
        const $dropdown_menu = $element.find(".steps-step-action-dropdown");

        self.dropdown = createDropdown($trigger[0], {
            dropdown_menu: $dropdown_menu[0],
        });
    }

    function setNewStatusIfNotSaving(status) {
        if (self.saving) {
            return;
        }

        setNewStatus(status);
        self.dropdown.hide();
    }

    function setNewStatus(status) {
        self.saving = true;
        resetError();

        return ExecutionRestService.updateStepStatus(self.execution, self.step.id, status)
            .then(
                () => {
                    updateStepResults(self.execution, self.step.id, status);
                    self.step_result.status = status;
                    updateStatusWithStepResults(self.execution, ExecutionService);
                    ExecutionService.updatePresencesOnCampaign();
                },
                (error) =>
                    setError(
                        gettextCatalog.getString(
                            "An error occurred while executing this step. Please try again later. {{ error }}",
                            { error },
                        ),
                    ),
            )
            .finally(() => {
                self.saving = false;
            });
    }

    function sanitizedContentWithEnhancedCodeBlocks(html_content) {
        return $sce.trustAsHtml(
            sanitize(html_content, {
                ADD_TAGS: ["tlp-mermaid-diagram"],
            }),
        );
    }
}
