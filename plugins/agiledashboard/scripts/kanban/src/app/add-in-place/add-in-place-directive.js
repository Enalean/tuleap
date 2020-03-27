import "./add-in-place.tpl.html";
import AddInPlaceController from "./add-in-place-controller.js";

export default AddInPlace;

AddInPlace.$inject = [];

function AddInPlace() {
    return {
        restrict: "E",
        scope: {
            column: "=",
            createItem: "=",
        },
        templateUrl: "add-in-place.tpl.html",
        controller: AddInPlaceController,
        controllerAs: "addInPlace",
        bindToController: true,
    };
}
