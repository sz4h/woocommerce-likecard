<table>
	<tr>
		<th><?php _e('Share',SPWL_TD); ?></th>
		<th><?php _e('Code',SPWL_TD); ?></th>
		<th><?php _e('Copy',SPWL_TD); ?></th>
	</tr>
	<?php foreach($serials as $serial) : ?>
	<tr>
        <td><a href=""><i class='lar la-share-square'></i></a></td>
        <td><?php echo $serial['serial']; ?></td>
        <td><a href=""><i class='lar la-share-square'></i></a></td>
    </tr>
    <tr>
        <td colspan="3"><?php _e('Valid till'); ?> : <?php echo $serial['valid']; ?></td>
    </tr>
	<?php endforeach; ?>
</table>