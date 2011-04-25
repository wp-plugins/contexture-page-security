<h3><?php _e('Restrict Access','contexture-page-security') ?></h3>
<table class="form-table">
    <tr>
        <th scope="row" valign="top"><label for="prot-term"><?php _e('Protect Term','contexture-page-security'); ?></label></th>
        <td>
            <input name="prot-term" id="prot-term" type="checkbox" <?php echo ($protected_status) ? 'checked="checked"' : ''; ?>/>
            <label for="prot-term"><?php _e('Protect this term and any content associated with it.','contexture-page-security') ?></label>
        </td>
    </tr>
</table>
<p></p>
<div style="display:none;">
<?php new CTXPS_Table_Packages('taxonomy_term_groups'); ?>
</div>