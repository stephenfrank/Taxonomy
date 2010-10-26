<?=form_open($_form_base.AMP.'method=update_trees')?>

<?php
	
	$selected_templates = explode('|', $tree_info['template_preferences']);
	$selected_channels = explode('|', $tree_info['channel_preferences']);
	
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
								array('data' => lang('option'), 'style' => 'width:200px'),
								array('data' => lang('value'), 'style' => '')
							);

	$this->table->add_row(
	form_hidden('id', $tree_info['id']).
	lang('tree_label'),
	form_input('label', set_value('label', $tree_info['label'] ), 'id="tree_label"')								
	);
	
	$this->table->add_row(
	lang('template_preferences'),
	form_multiselect('template_preferences[]', $templates, $selected_templates, 'class="taxonomy-multiselect"')
	);
	
	$this->table->add_row(
	lang('channel_preferences'),
	form_multiselect('channel_preferences[]', $channels, $selected_channels, 'class="taxonomy-multiselect"')	
	);

	echo $this->table->generate();
	$this->table->clear(); // needed to reset the table
?>

<input type="submit" class="submit" value="<?=lang('save_settings')?>" />
<?=form_close()?>