import { isUndefined } from "angular";
import { pick } from "lodash";

export default OpenListFieldValidateService;

OpenListFieldValidateService.$inject = [];

function OpenListFieldValidateService() {
    return {
        validateFieldValue: validateFieldValue
    };

    function validateFieldValue(value_model) {
        if (isUndefined(value_model)) {
            return null;
        }

        value_model.value.bind_value_objects = value_model.value.bind_value_objects.map(function(
            bind_value_object
        ) {
            if (value_model.bindings.type === "static") {
                return removeStaticValueUnusedAttributes(bind_value_object);
            } else if (value_model.bindings.type === "ugroups") {
                return removeUgroupsValueUnusedAttributes(bind_value_object);
            } else if (value_model.bindings.type === "users") {
                return removeUsersValueUnusedAttributes(bind_value_object);
            }
        });

        return removeValueModelUnusedAttributes(value_model);
    }

    function removeStaticValueUnusedAttributes(static_bind_value) {
        return pick(static_bind_value, ["id", "label"]);
    }

    function removeUgroupsValueUnusedAttributes(ugroups_bind_value) {
        return pick(ugroups_bind_value, ["id", "short_name"]);
    }

    function removeUsersValueUnusedAttributes(users_bind_value) {
        if (users_bind_value.is_anonymous) {
            return pick(users_bind_value, ["email"]);
        }
        return pick(users_bind_value, ["id", "username", "email"]);
    }

    function removeValueModelUnusedAttributes(value_model) {
        return pick(value_model, ["field_id", "value"]);
    }
}
