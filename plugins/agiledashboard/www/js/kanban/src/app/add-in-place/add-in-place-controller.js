import _ from 'lodash';

export default AddInPlaceCtrl;

AddInPlaceCtrl.$inject = [];

function AddInPlaceCtrl() {
    var self    = this,
        is_open = false,
        column,
        createItem;

    _.extend(self, {
        summary:   '',
        isOpen:    isOpen,
        close:     close,
        open:      open,
        submit:    submit,
        init:      init
    });

    function isOpen() {
        return is_open;
    }

    function close() {
        self.summary = '';
        is_open      = false;
    }

    function open() {
        is_open = true;
    }

    function init(col, createItemCallback) {
        column     = col;
        createItem = createItemCallback;
    }

    function submit() {
        var label = self.summary.trim();

        if (! label) {
            return;
        }

        createItem(label, column);

        self.summary = '';
    }
}
