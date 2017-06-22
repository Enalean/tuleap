import './card-computed-field.tpl.html';

export default function cardComputedField() {
    return {
        restrict: 'AE',
        scope   : {
            card_field  : '=field',
            filter_terms: '=filterTerms'
        },
        templateUrl: 'card-computed-field.tpl.html'
    };
}
