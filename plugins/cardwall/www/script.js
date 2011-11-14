document.observe('dom:loaded',function () {
    $$('.tracker_renderer_board').each(function (board) {
        //{{{ Make sure that we got the last version of the card wall
        //    Eg: board > drag n drop > go to a page > click back (the post it should be drag 'n dropped)
        if ($F('tracker_report_cardwall_to_be_refreshed') == 1) {
            $('tracker_report_cardwall_to_be_refreshed').value = 0;
            location.reload();
        } else {
            $('tracker_report_cardwall_to_be_refreshed').value = 1;
        }
        // }}}
        
        board.select('.tracker_renderer_board_postit').each(function (postit) {
            new Draggable(postit, {
                revert: 'failure'
            });
        });
        board.select('col').each(function (col) {
            Droppables.add(col, {
                hoverclass: 'tracker_renderer_board_column_hover',
                accept: 'tracker_renderer_board_postit',
                onDrop: function (dragged, dropped, event) {
                    var cursor = Element.getStyle(dragged, 'cursor');
                    Element.setStyle(dragged, {
                        cursor: 'progress'
                    });
                    var parameters = {
                        aid: dragged.id.split('-')[1],
                        func: 'artifact-update'
                    };
                    parameters['artifact['+ $F('tracker_report_cardwall_settings_column') +']'] = dropped.id.split('-')[1];
                    new Ajax.Request(location.href, {
                        method: 'POST',
                        parameters: parameters,
                        onSuccess: function() {
                            Element.remove(dragged);
                            dragged.setStyle({
                                zIndex: 'auto', 
                                left: 'auto',
                                top: 'auto'
                            });
                            col.up('table').down('tbody tr').down('td', col.up().childElements().indexOf(col)).down('ul').appendChild(dragged);
                            Element.setStyle(dragged, {
                                cursor: cursor
                            });
                            dropped.highlight();
                        }
                    });
                }
            });
        });
    });
});
