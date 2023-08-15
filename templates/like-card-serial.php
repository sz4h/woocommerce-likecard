<table class="like-table">
    <tr>
        <th><?php _e( 'Share', SPWL_TD ); ?></th>
        <th><?php _e( 'Code', SPWL_TD ); ?></th>
        <th><?php _e( 'Copy', SPWL_TD ); ?></th>
    </tr>
	<?php foreach ( $serials as $serial ) : ?>
        <tr>
            <td><a href="#" onclick="shareCode('<?php echo $serial['serial']; ?>')"><i class='lar la-share-square'></i></a></td>
            <td>
                <label for="code_<?php echo $item->get_id(); ?>">
                    <input type="text" id="code_<?php echo $item->get_id(); ?>"
                           value="<?php echo $serial['serial']; ?>">
                </label>
            </td>
            <td>
                <a href="#" onclick="cpCode('code_<?php echo $item->get_id(); ?>')">
                    <i class='lar la-copy'></i>
                </a>
            </td>
        </tr>
        <tr>
            <td colspan="3"><?php _e( 'Valid till' ); ?> : <?php echo $serial['valid']; ?></td>
        </tr>
	<?php endforeach; ?>
</table>