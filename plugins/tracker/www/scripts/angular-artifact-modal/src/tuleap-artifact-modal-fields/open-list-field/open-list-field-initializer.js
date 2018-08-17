import { uniq } from "lodash";

export { formatDefaultValue, formatExistingValue };

function formatDefaultValue(field) {
    const { field_id, type, permissions, default_value, bindings } = field;
    const value = {
        bind_value_objects: default_value ? [].concat(field.default_value) : []
    };

    return {
        field_id,
        type,
        permissions,
        bindings,
        value
    };
}

function formatExistingValue(field, artifact_value) {
    const { field_id, type, permissions, bindings } = field;
    const value = {
        bind_value_objects: uniq(artifact_value.bind_value_objects, item => {
            if (item.is_anonymous) {
                return item.email;
            }
            return item.id;
        })
    };

    return {
        field_id,
        type,
        permissions,
        bindings,
        value
    };
}
