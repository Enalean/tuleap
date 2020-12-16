import "./card-parent-link.tpl.html";

export default () => {
    return {
        restrict: "AE",
        scope: {
            text: "@",
            url: "@",
            filter_terms: "@filterTerms",
        },
        templateUrl: "card-parent-link.tpl.html",
    };
};
