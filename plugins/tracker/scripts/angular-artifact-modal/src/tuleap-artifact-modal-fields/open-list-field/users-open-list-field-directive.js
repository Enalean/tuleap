import "./users-open-list-field.tpl.html";
import UsersOpenListFieldController from "./users-open-list-field-controller.js";

export default function UsersOpenListFieldDirective() {
    return {
        restrict: "EA",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalUsersOpenListField",
            isDisabled: "&isDisabled",
            value_model: "=valueModel",
        },
        controller: UsersOpenListFieldController,
        controllerAs: "users_open_list_field",
        bindToController: true,
        templateUrl: "users-open-list-field.tpl.html",
    };
}
