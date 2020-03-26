let is_updated;
resetUpdated();

export function isUpdated() {
    return is_updated;
}

export function theTestHasJustBeenUpdated() {
    is_updated = true;
}

export function resetUpdated() {
    is_updated = false;
}
