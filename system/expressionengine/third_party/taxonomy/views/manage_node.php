<script type="text/javascript">

<?php if($site_pages):?>

<?php 
	$select_page_uri_option = "<div id='taxonomy_use_page_uri'><div".$hide.">".form_checkbox($site_pages_checkbox_options)." ".lang('use_pages_module_uri')."</div></div>"; 
?>

jQuery.fn.detectPageURI = function()
{
	var url = '<?= $has_page_ajax_url ?>' + $(this).val();
    $.getJSON(url, function(data) 
    {
    	if(data.page_uri != null && data.page_uri != false)
    	{
    		$('#taxonomy_use_page_uri div').fadeIn();
    	}
    	else
    	{
    		$('#taxonomy_use_page_uri div').fadeOut();
    		$('#taxonomy_use_page_uri input').attr('checked', false);
    		$('#taxonomy_select_template').show();
			var custom_url = $('#custom_url').val();
		    if(custom_url == '[page_uri]')
		    {
		    	$('#custom_url').show().val('');
		    }
    	}
	});
}

<?php else: ?>
// null it, we don't have pages
jQuery.fn.detectPageURI = function(){}
<?php endif ?>
</script>

<script type="text/javascript" src="<?=$_theme_base_url?>js/taxonomy.js"></script>

<?php 
	if($root_insert) echo lang('root_node_notice');
	
	echo form_open($_form_base_url.AMP.'method=update_node');
	
	echo form_hidden('node_id', $node_id);
	echo form_hidden('tree_id', $tree_id);
	echo form_hidden('is_root', $root_insert);
	
	$this->table->set_template($cp_table_template);
	
	$this->table->set_heading(
			array('data' => lang('option'), 'class' => 'options'),
			array('data' => lang('value'))
		);
	
	$this->table->add_row(
		lang('node_label'),
		form_input('label', htmlspecialchars_decode($label), 'id="label", style="width: 60%;"')
	);
	
	// don't show select parent for root insertion, or node editing
	if(!$root_insert && !$node_id)
	{
		$this->table->add_row(
			lang('select_parent'), 
			form_dropdown('parent', $parent_select)
		);
	}
	
	// trees can be associated with template groups and channels
	// cover various options of these combinations, remove fields if they're not being used.
	if(!count($templates) && !count($channel_entries))
	{
		// no templates, no entries... just the url override it is... you never know?
	}
	elseif(count($templates) && !count($channel_entries))
	{
		// we got templates, no entries
		$this->table->add_row(
			lang('internal_url_no_entries'),
			form_dropdown('template', $templates, $template_path)
		);
	}
	elseif(!count($templates) && count($channel_entries))
	{
		// we got entries, no templates
		$this->table->add_row(
			lang('internal_url_no_templates'),
			"<div id='taxonomy_select_entry'>".$entries_select_dropdown."</div>"
		);
	}
	else
	{
		// we got entries and templates
		$this->table->add_row(
			lang('internal_url'),
			"<div id='taxonomy_select_template'>".form_dropdown('template', $templates, $template_path)." &nbsp; </div>".
			"<div id='taxonomy_select_entry'>".$entries_select_dropdown."</div>"
		);
	}
	
	// custom url with option to use pages module uri
	$this->table->add_row(
		lang('override_url'),
		form_input('custom_url', $custom_url, 'id="custom_url", style="width: 60%; float:left;"').$select_page_uri_option
	);
	
	
	
	// lcustom fields
	if(isset($tree_settings['fields']) && is_array($tree_settings['fields']))
	{
		foreach($tree_settings['fields'] as $custom_field)
		{
			
			$value = (isset($field_data[$custom_field['name']])) ? $field_data[$custom_field['name']] : '';
			
			switch($custom_field)
			{
				case($custom_field['type'] == 'text'):
					$this->table->add_row(
						$custom_field['label'].':',
						form_input('custom_fields['.$custom_field['name'].']', $value, 'id='.$custom_field['name'].',  style="width: 60%;"')
					);
					break;
				case($custom_field['type'] == 'checkbox'):
					$this->table->add_row(
						'&nbsp;',
						form_checkbox('custom_fields['.$custom_field['name'].']', 1, $value).' &nbsp; '.$custom_field['label']
					);
					break;
				case($custom_field['type'] == 'textarea'):
					$this->table->add_row(
						$custom_field['label'].':',
						form_textarea('custom_fields['.$custom_field['name'].']', $value, 'id='.$custom_field['name'].',  style="width: 60%; height:60px;"')

					);
					break;
			}
			
		}
	}
	

	// submit or cancel
	$this->table->add_row(
		'&nbsp;',
		'<input type="submit" name="submit" value="Submit" class="submit"  /> 
		&nbsp; &nbsp; 
		<a href="'.$_base_url.'&method=edit_nodes&tree_id='.$tree_id.'" class="taxononomy-cancel">Cancel</a>'
	);
	
	echo $this->table->generate();
	
	$this->table->clear();
	
	echo form_close();

?>