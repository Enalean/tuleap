document.observe('dom:loaded',function () {
    $$('.tracker_renderer_board').each(function (board) {
        board.select('.tracker_renderer_board_postit').each(function (postit) {
            new Draggable(postit, {
                //revert: true
            });
        });
        board.select('td').each(function (col) {
            Droppables.add(col, {
                hoverclass: 'tracker_renderer_board_column_hover',
                accept: 'tracker_renderer_board_postit',
                onDrop: function (dragged, dropped, event) {
                    Element.remove(dragged);
                    dragged.setStyle({
                        zIndex: 'auto', 
                        left: 'auto',
                        top: 'auto'
                    });
                    col.down('ul').appendChild(dragged);
                    dropped.highlight(); 
                }
            });
        });
    });
});
