<h3 id="term-restrict"><?php _e('Restrict Access','contexture-page-security') ?></h3>
<table class="form-table">
    <tr>
        <th scope="row" valign="top"><label for="ctxps-cb-protect"><?php _e('Protect Term','contexture-page-security'); ?></label></th>
        <td>
            <input name="ctxps-cb-protect" id="ctxps-cb-protect" type="checkbox" <?php echo ($protected_status) ? 'checked="checked"' : ''; ?>/>
            <label for="ctxps-cb-protect"><?php _e('Protect this term and any content associated with it.','contexture-page-security') ?></label>
        </td>
    </tr>
</table>
<p></p>
<div id="ctxps-relationships-list" style="<?php echo ($protected_status) ? 'display:block;' : ''; ?>padding-left:7px;">
    <!--<h4><?php _e('Groups With Access','contexture-page-security') ?></h4>-->
    <div class="tablenav top">
        <div class="alignleft actions">
            <label class="screen-reader-text" for="ctxps-add-group"><?php _e('Add group...','contexture-page-security') ?></label>
            <select name="ctxps-add-group" id="ctxps-add-group">
                <option><?php _e('Add group...','contexture-page-security') ?></option>
            </select>
            <input type="button" name="ctxps-add-group-btn" id="ctxps-add-group-btn" class="button-secondary" value="Add">
        </div>
    </div>
    <?php new CTXPS_Table_Packages('taxonomy_term_groups'); ?>
</div>