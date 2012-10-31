<? if ($section->changes): ?>
<tr>
    <td>
        <ul>
            <? foreach ($section->changes as $change): ?>
            <li><?= nl2br($change) ?></li>
            <? endforeach; ?>
        </ul>
    </td>
</tr>
<? endif; ?>

