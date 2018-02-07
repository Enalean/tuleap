import _ from 'lodash';

export default tuleapSimpleField;

tuleapSimpleField.$inject = [
    '$sce'
];

function tuleapSimpleField(
    $sce
) {
    return {
        restrict: 'AE',
        scope   : {
            value       : '=',
            filter_terms: '=filterTerms'
        },
        template: '<span ng-bind-html="getDisplayableValue(value) | tuleapHighlight:filter_terms"></span>',
        link    : link
    };

    function link(scope) {
        scope.getDisplayableValue = getDisplayableValue;
    }

    function getDisplayableValue(value) {
        return $sce.trustAsHtml(_.escape(value));
    }
}
