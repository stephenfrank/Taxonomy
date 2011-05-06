<p class="taxonomy-instructions"><?=lang('create_tree_instructions')?></p>
<?php
	
	echo form_open($_form_base_url.AMP.'method=update_tree');

	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
								array('data' => lang('option'), 'style' => 'width:200px'),
								array('data' => lang('value'), 'style' => '')
							);

	$this->table->add_row(
		form_hidden('tree_id', '').
		lang('tree_label'),
		form_input('label', set_value('label', ''), 'id="tree_label"')								
	);
	
	$this->table->add_row(
		lang('template_preferences'),
		form_multiselect('templates[]', $templates, '', 'class="taxonomy-multiselect"')
	);
	
	$this->table->add_row(
		lang('channel_preferences'),
		form_multiselect('channels[]', $channels, '', 'class="taxonomy-multiselect"')	
	);
	
	// only show this if we have available member groups
	if(isset($member_groups))
	{
		$this->table->add_row(
			lang('member_preferences'),
			form_multiselect('member_groups[]', $member_groups, '', 'class="taxonomy-multiselect"')	
		);
	}

	echo $this->table->generate();
	$this->table->clear(); // needed to reset the table
?>

<input type="submit" class="submit" value="<?=lang('create_tree')?>" />
<?=form_close()?>