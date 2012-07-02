<div class="cardwall_board <?= $this->nifty ?>">
    
    <? foreach($this->board->mappings as $mapping): ?>
        <input type="hidden"
               id="cardwall_column_mapping_<?= $mapping->column_id ?>_<?= $mapping->field_id ?>"
               value="<?= $mapping->value_id ?>" />
    <? endforeach; ?>
        
    <table width="100%"
           border="1"
           bordercolor="#ccc"
           cellspacing="2"
           cellpadding="10">
        
        <colgroup>
            <? if ($this->has_swimline_header): ?>
                <col />
            <? endif; ?>
            
            <? foreach($this->board->columns as $column): ?>
                <col id="cardwall_board_column-<?= $column->id ?>" />
            <? endforeach; ?>
        </colgroup>

        <thead>
            <tr>
                <? if ($this->has_swimline_header): ?>
                    <th><?= $this->swimline_title ?></th>
                <? endif; ?>
                    
                <? foreach($this->board->columns as $column): ?>
                    <th style="background-color: <?= $column->bgcolor ?>;
                               color: <?= $column->fgcolor ?>">
                        <?= $column->label ?>
                    </th>
                <? endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <? foreach($this->board->swimlines as $swimline): ?>
                <tr valign="top">
                    <? if ($this->has_swimline_header): ?>
                        <? if ($swimline->node): ?>
                            <td>
                                <ul class="nodrag">
                                    <li>
                                        <?= $this->render('Cardwall_CardView', $swimline->node->getObject()); ?>
                                    </li>
                                </ul>
                            </td>
                        <? endif; ?>
                    <? endif; ?>
                    
                    <? foreach ($swimline->cells as $cell): ?>
                        <td>
                            <ul>
                                <? foreach ($cell['artifacts'] as $artifact_node): ?>
                                    <? $data = $artifact_node->getData() ?>
                                    <li class="cardwall_board_postit <?= $data['drop-into-class'] ?>"
                                        id="cardwall_board_postit-<?= $artifact_node->getId() ?>"
                                        data-column-field-id="<?= $data['column_field_id'] ?>">
                                            <?= $this->render('Cardwall_CardView', $artifact_node->getObject()); ?>
                                    </li>
                                <? endforeach; ?>
                            </ul>
                        </td>
                    <? endforeach; ?>
                </tr>
            <? endforeach; ?>
        </tbody>
    </table>
</div>
