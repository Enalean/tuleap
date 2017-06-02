import modalInit     from './dashboard-modals.js';
import dropdownInit  from './dashboard-dropdowns.js';
import addWidgetInit from './dashboard-add-widget.js';
import minimizeInit  from './dashboard-minimize.js';
import dragDropInit  from './dashboard-drag-drop.js';

document.addEventListener('DOMContentLoaded', function () {
    modalInit();
    dropdownInit();
    dragDropInit();
    addWidgetInit();
    minimizeInit();
});
