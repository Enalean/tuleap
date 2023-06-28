export default () => {
    return {
        restrict: "AE",
        scope: {
            value: "@",
            filter_terms: "@filterTerms",
        },
        template:
            '<span ng-bind-html="value | tuleapHighlight:filter_terms" data-test="tuleap-simple-field-name"></span>',
    };
};
