export const BACKLOG_COLUMN = "backlog";
export const ARCHIVE_COLUMN = "archive";

export const isBacklog = (column_identifier) => column_identifier === BACKLOG_COLUMN;
export const isArchive = (column_identifier) => column_identifier === ARCHIVE_COLUMN;
