(function () {
    angular
        .module('shared-properties')
        .service('SharedPropertiesService', SharedPropertiesService);

    function SharedPropertiesService() {
        var property = {
            project_id:   undefined,
            milestone_id: undefined
        };

        return {
            getProjectId:   getProjectId,
            setProjectId:   setProjectId,
            getMilestoneId: getMilestoneId,
            setMilestoneId: setMilestoneId
        };

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
    }
})();