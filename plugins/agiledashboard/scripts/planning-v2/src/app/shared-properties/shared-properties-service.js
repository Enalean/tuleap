export default function SharedPropertiesService() {
    var property = {
        user_id: undefined,
        view_mode: undefined,
        project_id: undefined,
        milestone_id: undefined,
        milestone: undefined,
        initial_backlog_items: undefined,
        initial_milestones: undefined,
        is_in_explicit_top_backlog: undefined,
    };

    return {
        getUserId: getUserId,
        setUserId: setUserId,
        getViewMode: getViewMode,
        setViewMode: setViewMode,
        getProjectId: getProjectId,
        setProjectId: setProjectId,
        getMilestoneId: getMilestoneId,
        setMilestoneId: setMilestoneId,
        getMilestone: getMilestone,
        setMilestone: setMilestone,
        getInitialBacklogItems: getInitialBacklogItems,
        setInitialBacklogItems: setInitialBacklogItems,
        getInitialMilestones: getInitialMilestones,
        setInitialMilestones: setInitialMilestones,
        isInExplicitTopBacklogManagement: isInExplicitTopBacklogManagement,
        setIsInExplicitTopBacklogManagement: setIsInExplicitTopBacklogManagement,
    };

    function getUserId() {
        return property.user_id;
    }

    function setUserId(user_id) {
        property.user_id = user_id;
    }

    function getViewMode() {
        return property.view_mode;
    }

    function setViewMode(view_mode) {
        property.view_mode = view_mode;
    }

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getMilestoneId() {
        return property.milestone_id;
    }

    function setMilestoneId(milestone_id) {
        property.milestone_id = milestone_id;
    }

    function getMilestone() {
        return property.milestone;
    }

    function setMilestone(milestone) {
        property.milestone = milestone;
    }

    function getInitialBacklogItems() {
        return property.initial_backlog_items;
    }

    function setInitialBacklogItems(initial_backlog_items) {
        property.initial_backlog_items = initial_backlog_items;
    }

    function getInitialMilestones() {
        return property.initial_milestones;
    }

    function setInitialMilestones(initial_milestones) {
        property.initial_milestones = initial_milestones;
    }

    function isInExplicitTopBacklogManagement() {
        return property.is_in_explicit_top_backlog;
    }

    function setIsInExplicitTopBacklogManagement(is_in_explicit_top_backlog) {
        property.is_in_explicit_top_backlog = is_in_explicit_top_backlog;
    }
}
