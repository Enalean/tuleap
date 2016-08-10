angular
    .module('card-fields')
    .directive('cardComputedField', cardComputedField);

function cardComputedField() {
    return {
        restrict    : 'AE',
        scope       : {
            card_field: '=field'
        },
        templateUrl: 'card-fields/card-computed-field/card-computed-field.tpl.html'
    };
}
