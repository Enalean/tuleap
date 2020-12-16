import "./card-parent-link.tpl.html";

export default () => {
    return {
        restrict: "AE",
        scope: {
            text: "@",
            url: "@",
            parent_project_label: "@parentProjectLabel",
            parent_project_id: "@parentProjectId",
            project_id: "@projectId",
            filter_terms: "@filterTerms",
        },
        templateUrl: "card-parent-link.tpl.html",
    };
};
