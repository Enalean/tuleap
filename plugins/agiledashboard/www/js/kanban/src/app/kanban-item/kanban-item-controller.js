angular
    .module('kanban-item')
    .controller('KanbanItemController', KanbanItemController);

KanbanItemController.$inject = [
    'CardFieldsService',
    'KanbanFilterValue'
];

function KanbanItemController(
    CardFieldsService,
    KanbanFilterValue
) {
    var self = this;

    _.extend(self, {
        kanban_filter               : KanbanFilterValue,
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
        getCardFieldUserValue       : CardFieldsService.getCardFieldUserValue,
    });
}
