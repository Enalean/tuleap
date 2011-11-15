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
        
        // {{{ Define the draggables cards
        board.select('.tracker_renderer_board_postit').each(function (postit) {
            new Draggable(postit, {
                revert: 'failure'
            });
        });
        // }}}
        
        // {{{ Define the droppables columns
        var cols = board.select('col'),
            i = 0,
            cols_classnames = [];
        cols.each(function (col) {
            var c = board.identify() + '_dummy_' + i;
            cols_classnames[i] = c;
            col.up('table').down('tbody tr').down('td', col.up().childElements().indexOf(col)).select('.tracker_renderer_board_postit').invoke('addClassName', c);
            i++;
        });
        cols.each(function (col) {
            var col_index = cols.indexOf(col),
                td = col.up('table').down('tbody tr').down('td', col_index);
            Droppables.add(td, {
                hoverclass: 'tracker_renderer_board_column_hover',
                accept: cols_classnames.reject(function (value, key) {
                    return key == col_index;
                }),
                onDrop: function (dragged, dropped, event) {
                    var cursor = Element.getStyle(dragged, 'cursor');
                    Element.setStyle(dragged, {
                        cursor: 'progress'
                    });
                    var parameters = {
                        aid: dragged.id.split('-')[1],
                        func: 'artifact-update'
                    };
                    parameters['artifact['+ $F('tracker_report_cardwall_settings_column') +']'] = col.id.split('-')[1];
                    new Ajax.Request(location.href, {
                        method: 'POST',
                        parameters: parameters,
                        onSuccess: function() {
                            //change the classname of the post it to be accepted by the formers columns
                            dragged.removeClassName(cols_classnames[dragged.up('tr').childElements().indexOf(dragged.up('td'))]);
                            dragged.addClassName(cols_classnames[col_index]);
                            
                            //switch to the new column
                            Element.remove(dragged);
                            dragged.setStyle({
                                zIndex: 'auto', 
                                left: 'auto',
                                top: 'auto'
                            });
                            td.down('ul').appendChild(dragged);
                            Element.setStyle(dragged, {
                                cursor: cursor
                            });
                            td.highlight();
                        }
                    });
                }
            });
        });
        // }}}
    });
});
