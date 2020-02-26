import moment from "moment";
import { setAccessibilityMode } from "./user-accessibility-mode.js";

export default MainController;

MainController.$inject = ["$element", "SharedPropertiesService", "amMoment", "gettextCatalog"];

function MainController($element, SharedPropertiesService, amMoment, gettextCatalog) {
    init();

    function init() {
        const planning_init_data = $element[0].querySelector(".planning-init-data").dataset;

        const user_id = planning_init_data.userId;
        SharedPropertiesService.setUserId(user_id);
        const project_id = planning_init_data.projectId;
        SharedPropertiesService.setProjectId(project_id);
        const milestone_id = planning_init_data.milestoneId;
        SharedPropertiesService.setMilestoneId(milestone_id);
        const milestone = JSON.parse(planning_init_data.milestone);
        SharedPropertiesService.setMilestone(milestone);
        const initial_backlog_items = JSON.parse(planning_init_data.paginatedBacklogItems);
        SharedPropertiesService.setInitialBacklogItems(initial_backlog_items);
        const initial_milestones = JSON.parse(planning_init_data.paginatedMilestones);
        SharedPropertiesService.setInitialMilestones(initial_milestones);
        const view_mode = planning_init_data.viewMode;
        SharedPropertiesService.setViewMode(view_mode);
        const is_in_explicit_top_backlog = planning_init_data.isInExplicitTopBacklog;
        SharedPropertiesService.setIsInExplicitTopBacklogManagement(is_in_explicit_top_backlog);
        setAccessibilityMode(JSON.parse(planning_init_data.userAccessibilityMode));

        const language = planning_init_data.language;
        initLocale(language);
    }

    function initLocale(lang) {
        gettextCatalog.setCurrentLanguage(lang);
        amMoment.changeLocale(lang);
        moment.locale(lang);
    }
}
