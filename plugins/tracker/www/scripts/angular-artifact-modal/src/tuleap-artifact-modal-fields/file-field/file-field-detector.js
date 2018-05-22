export {
    getAllFileFields,
    isThereAtLeastOneFileField
};

function getAllFileFields(tracker_fields) {
    return tracker_fields.filter(field => field.type === 'file');
}

function isThereAtLeastOneFileField(tracker_fields) {
    return tracker_fields.some(field => field.type === 'file');
}
