import moment from "moment";

export { formatExistingValue };

function formatExistingValue(field, artifact_value) {
    const { field_id, type, permissions, is_time_displayed } = field;

    return {
        field_id,
        type,
        permissions,
        value: getValue(artifact_value.value, is_time_displayed),
    };
}

function getValue(value, is_time_displayed) {
    if (value === null) {
        return "";
    }

    if (is_time_displayed) {
        return moment(value, moment.ISO_8601).format("YYYY-MM-DD HH:mm");
    }

    return moment(value, moment.ISO_8601).format("YYYY-MM-DD");
}
