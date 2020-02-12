export default CardFieldsController;

CardFieldsController.$inject = ["CardFieldsService"];

function CardFieldsController(CardFieldsService) {
    const self = this;
    Object.assign(self, CardFieldsService);
}
