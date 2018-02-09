import { escape } from 'lodash';

export default tuleapCardLink;

tuleapCardLink.$inject = [
    '$sce'
];

function tuleapCardLink(
    $sce
) {
    return {
        restrict: 'AE',
        scope   : {
            text        : '@',
            url         : '@',
            filter_terms: '@filterTerms'
        },
        template: `<a data-nodrag="true" ng-href="{{ url }}"
                        ng-bind-html="getDisplayableValue() | tuleapHighlight:filter_terms"
                    ></a>`,
        link
    };

    function link(scope) {
        scope.getDisplayableValue = () => $sce.trustAsHtml(escape(scope.text));
    }
}
