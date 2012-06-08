document.observe('dom:loaded', function () {
    $$('.cardwall_board').each(function (board) {
        //{{{ Make sure that we got the last version of the card wall
        if ($('tracker_report_cardwall_to_be_refreshed')) {
            //    Eg: board > drag n drop > go to a page > click back (the post it should be drag 'n dropped)
            if ($F('tracker_report_cardwall_to_be_refreshed') === "1") {
                $('tracker_report_cardwall_to_be_refreshed').value = 0;
                location.reload();
            } else {
                $('tracker_report_cardwall_to_be_refreshed').value = 1;
            }
        }
        // }}}

        // {{{ Define the draggables cards
        board.select('.cardwall_board_postit').each(function (postit) {
            var d = new Draggable(postit, {
                revert: 'failure'
            });
        });
        // }}}

        // {{{ Define the droppables columns
        var cols = board.select('col'),
            cols_classnames = [];
        cols.each(function (col, col_index) {
            cols_classnames[col_index] = [];
            col.up('table').down('tbody').select('tr').each(function (tr, swimline_index) {
                var c = board.identify() + '_dummy_' + col_index + '_' + swimline_index;
                cols_classnames[col_index][swimline_index] = c;
                tr.down('td', col.up().childElements().indexOf(col)).select('.cardwall_board_postit').invoke('addClassName', c);
            });
        });
        cols.each(function (col, col_index) {
            col.up('table').down('tbody').select('tr').each(function (tr, swimline_index) {
                var td           = tr.down('td', col_index),
                    restorecolor = td.getStyle('background-color'),
                    effect       = null;
                Droppables.add(td, {
                    hoverclass: 'cardwall_board_column_hover',
                    accept: cols_classnames.collect(function (swim) {
                        return swim[swimline_index];
                    }).reject(function (value, key) {
                        return key === col_index;
                    }),
                    onDrop: function (dragged, dropped, event) {
                        //change the classname of the post it to be accepted by the formers columns
                        dragged.removeClassName(cols_classnames[dragged.up('tr').childElements().indexOf(dragged.up('td'))][swimline_index]);
                        dragged.addClassName(cols_classnames[col_index][swimline_index]);
    
                        //switch to the new column
                        Element.remove(dragged);
                        td.down('ul').appendChild(dragged);
                        dragged.setStyle({
                            left: 'auto',
                            top: 'auto'
                        });
                        if (effect) {
                            effect.cancel();
                        }
                        effect = new Effect.Highlight(td, {
                            restorecolor: restorecolor
                        });
    
                        //save the new state
                        var parameters = {
                                aid: dragged.id.split('-')[1],
                                func: 'artifact-update'
                            },
                            field_id,
                            value_id = col.id.split('-')[1];
                        if ($('tracker_report_cardwall_settings_column')) {
                             field_id = $F('tracker_report_cardwall_settings_column');
                        } else {
                            console.log(field_id, value_id);
                            field_id = dragged.readAttribute('data-column-field-id');
                            value_id = $F('cardwall_column_mapping_' + value_id + '_' + field_id);
                            console.log(field_id, value_id);
                        }
                        parameters['artifact[' + field_id + ']'] = value_id;
                        var req = new Ajax.Request(codendi.tracker.base_url, {
                            method: 'POST',
                            parameters: parameters,
                            onComplete: function () {
                                //TODO handle errors (perms, workflow, ...)
                                // eg: change color of the post it
                            }
                        });
                    }
                });
            });
        });
        // }}}
    });
});
