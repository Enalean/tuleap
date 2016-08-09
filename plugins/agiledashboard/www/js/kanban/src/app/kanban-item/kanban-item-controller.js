angular
    .module('kanban-item')
    .controller('KanbanItemController', KanbanItemController);

KanbanItemController.$inject = [
    'CardFieldsService'
];

function KanbanItemController(
    CardFieldsService
) {
    var self = this;

    _.extend(self, {
        cardFieldIsCross            : CardFieldsService.cardFieldIsCross,
        cardFieldIsDate             : CardFieldsService.cardFieldIsDate,
        cardFieldIsFile             : CardFieldsService.cardFieldIsFile,
        cardFieldIsList             : CardFieldsService.cardFieldIsList,
        cardFieldIsPermissions      : CardFieldsService.cardFieldIsPermissions,
        cardFieldIsSimpleValue      : CardFieldsService.cardFieldIsSimpleValue,
        cardFieldIsText             : CardFieldsService.cardFieldIsText,
        cardFieldIsUser             : CardFieldsService.cardFieldIsUser,
        cardFieldIsComputed         : CardFieldsService.cardFieldIsComputed,
        getCardFieldCrossValue      : CardFieldsService.getCardFieldCrossValue,
        getCardFieldDateValue       : CardFieldsService.getCardFieldDateValue,
        getCardFieldFileValue       : CardFieldsService.getCardFieldFileValue,
        getCardFieldListValues      : CardFieldsService.getCardFieldListValues,
        getCardFieldPermissionsValue: CardFieldsService.getCardFieldPermissionsValue,
        getCardFieldTextValue       : CardFieldsService.getCardFieldTextValue,
        getCardFieldUserValue       : CardFieldsService.getCardFieldUserValue,
    });
}
