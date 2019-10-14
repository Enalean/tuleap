import { RootState } from "../type";

export function hideClosedItems(state: RootState): void {
    state.are_closed_items_displayed = false;
}

export function displayClosedItems(state: RootState): void {
    state.are_closed_items_displayed = true;
}
