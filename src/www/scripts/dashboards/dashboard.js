import modalInit     from './dashboard-modals.js';
import dropdownInit  from './dashboard-dropdowns.js';
import asyncWidgetInit from './dashboard-async-widget.js';
import minimizeInit  from './dashboard-minimize.js';
import dragDropInit  from './dashboard-drag-drop.js';

document.addEventListener('DOMContentLoaded', function () {
    modalInit();
    dropdownInit();
    dragDropInit();
    asyncWidgetInit();
    minimizeInit();
});
