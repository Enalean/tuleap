export default KanbanFilteredUpdatedAlertService;

function KanbanFilteredUpdatedAlertService() {
    const property = {
        updated: false,
    };

    return {
        setCardHasBeenUpdated() {
            property.updated = true;
        },
        isCardUpdated() {
            return property.updated;
        },
    };
}
