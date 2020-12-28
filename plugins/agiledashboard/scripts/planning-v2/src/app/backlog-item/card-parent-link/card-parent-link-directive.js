import "./card-parent-link.tpl.html";

export default () => {
    return {
        restrict: "AE",
        scope: {
            text: "@",
            url: "@",
            parent_project_label: "@parentProjectLabel",
            parent_project_id: "@parentProjectId",
            parent_project_color_name: "@parentProjectColorName",
            project_id: "@projectId",
            filter_terms: "@filterTerms",
        },
        templateUrl: "card-parent-link.tpl.html",
        link(scope) {
            scope.getColorClass = function (color_name, parent_project_id, project_id) {
                if (!color_name || project_id === parent_project_id) {
                    return {};
                }
                return { [`project-color-${color_name}`]: true };
            };
        },
    };
};
