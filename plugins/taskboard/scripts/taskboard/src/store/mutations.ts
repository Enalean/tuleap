import { State } from "./type";

export function hideClosedItems(state: State): void {
    state.are_closed_items_displayed = false;
}

export function displayClosedItems(state: State): void {
    state.are_closed_items_displayed = true;
}
