<?php if ($section->changes): ?>
<tr>
    <td>
        <ul>
            <?php foreach ($section->changes as $change): ?>
            <li><?= nl2br($change) ?></li>
            <?php endforeach; ?>
        </ul>
    </td>
</tr>
<?php endif; ?>

