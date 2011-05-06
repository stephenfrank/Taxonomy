<?php
	
	
	echo form_open($_form_base_url.AMP.'method=update_tree');
	
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
								array('data' => lang('option'), 'style' => 'width:200px'),
								array('data' => lang('value'), 'style' => '')
							);

	$this->table->add_row(
	form_hidden('id', $tree['id']).
	lang('tree_label'),
	form_input('label', set_value('label', $tree['label'] ), 'id="tree_label"')								
	);
	
	$this->table->add_row(
	lang('template_preferences'),
	form_multiselect('template_preferences[]', $templates, $tree['template_preferences'], 'class="taxonomy-multiselect"')
	);
	
	$this->table->add_row(
	lang('channel_preferences'),
	form_multiselect('channel_preferences[]', $channels, $tree['channel_preferences'], 'class="taxonomy-multiselect"')	
	);
	
	if(count($member_groups))
	{
		$this->table->add_row(
		lang('member_preferences'),
		form_multiselect('member_group_preferences[]', $member_groups, $tree['permissions'], 'class="taxonomy-multiselect"')	
		);
	}

	echo $this->table->generate();
	$this->table->clear(); // needed to reset the table
?>


<div class="taxonomy-advanced-settings">
<h3><?=lang('advanced_settings')?></h3>
	<p><?=lang('advanced_settings_instructions')?></p>
	
<?php
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
								array('data' => lang('order'), 'style' => 'width:40px'),
								array('data' => lang('custom_field_label'), 'style' => 'width:200px'),
								array('data' => lang('custom_field_short'), 'style' => 'width:200px'),
								array('data' => lang('type'), 'style' => ''),
								array('data' => lang('display_on_publish'), 'style' => '')
							);
							
	$field_options = array('text'  => 'Text Input', 'textarea'  => 'Textarea',  'checkbox'  => 'Checkbox',);					

	// @todo move all this crap out of the view
	$i = 1;
	
	if(count($tree['fields']) > 0 && is_array($tree['fields']))
	{	
		// print_r($tree_info['extra']);
		
		foreach($tree['fields'] as $key => $field_row)
		{
	
			$order 	= (isset($field_row['order'])) ? $field_row['order'] : '';
			$label 	= (isset($field_row['label'])) ? $field_row['label'] : '';
			$name 	= (isset($field_row['name'])) ? $field_row['name'] : '';
			$type 	= (isset($field_row['type'])) ? $field_row['type'] : '';
			$show_on_publish = (isset($field_row['show_on_publish'])) ? $field_row['show_on_publish'] : FALSE;
			
			$this->table->add_row(
				form_input('fields['.$i.'][order]', $order, 'class="taxonomy-number-input"'),
				array('data' => form_input('fields['.$i.'][label]', $label, 'class="taxonomy-field-input"'), 'class' => 'foo'),
				form_input('fields['.$i.'][name]', $name, 'class="taxonomy-field-input"'),
				form_dropdown('fields['.$i.'][type]', $field_options, $type),
				form_checkbox('fields['.$i.'][show_on_publish]', '1', $show_on_publish)
			);
			
			$i++;
			
		}
		
	}
	
	// add our last blank row
	$order = $i;
	$label = '';
	$name = '';
	$type = '';
	$show_on_publish = FALSE;
	
	$this->table->add_row(
				form_input('fields['.$i.'][order]', $order, 'class="taxonomy-number-input"'),
				array('data' => form_input('fields['.$i.'][label]', $label, 'class="taxonomy-field-input"'), 'class' => 'foo'),
				form_input('fields['.$i.'][name]', $name, 'class="taxonomy-field-input"'),
				form_dropdown('fields['.$i.'][type]', $field_options, $type),
				form_checkbox('fields['.$i.'][show_on_publish]', '1', $show_on_publish)
			);
			
	
	echo $this->table->generate();
	$this->table->clear(); // needed to reset the table
?>
<p><?=lang('field_notice')?></p>
</div>








<input type="submit" name="update" class="submit" value="<?=lang('save')?>" />
<input type="submit" name="update_and_return" class="submit" value="<?=lang('save_and_close')?>" />

<?=form_close()?>