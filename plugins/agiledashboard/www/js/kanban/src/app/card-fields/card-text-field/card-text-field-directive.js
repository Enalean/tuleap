angular
    .module('card-fields')
    .directive('cardTextField', cardTextField);

function cardTextField() {
    var TEXT_FORMAT = 'text';
    var HTML_FORMAT = 'html';

    return {
        restrict: 'AE',
        scope   : {
            card_field  : '=field',
            filter_terms: '=filterTerms'
        },
        templateUrl: 'card-fields/card-text-field/card-text-field.tpl.html',
        link       : link
    };

    function link(scope) {
        scope.getDisplayableValue = getDisplayableValue;
    }

    function getDisplayableValue(text_field) {
        if (text_field.format === TEXT_FORMAT) {
            return _.escape(text_field.value);
        }

        if (text_field.format === HTML_FORMAT) {
            return text_field.value;
        }

        return text_field;
    }
}
