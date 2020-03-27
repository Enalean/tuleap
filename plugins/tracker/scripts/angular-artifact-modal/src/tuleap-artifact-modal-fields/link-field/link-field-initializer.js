export { formatExistingValue };

function formatExistingValue(field, artifact_value) {
    const { field_id, type, permissions } = field;
    const link_ids = artifact_value.links.map(({ id }) => id);
    const unformatted_links = link_ids.join(", ");

    return {
        field_id,
        type,
        permissions,
        unformatted_links,
        links: [{}],
    };
}
