export default () => {
    return {
        restrict: "AE",
        scope: {
            text: "@",
            url: "@",
            filter_terms: "@filterTerms",
        },
        template: `<a data-nodrag="true" ng-href="{{ url }}"
                        ng-bind-html="text | tuleapHighlight:filter_terms"
                    ></a>`,
    };
};
