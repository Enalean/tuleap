angular
    .module('card-fields')
    .directive('cardComputedField', cardComputedField);

function cardComputedField() {
    return {
        restrict: 'AE',
        scope   : {
            card_field  : '=field',
            filter_terms: '=filterTerms'
        },
        templateUrl: 'card-fields/card-computed-field/card-computed-field.tpl.html'
    };
}
