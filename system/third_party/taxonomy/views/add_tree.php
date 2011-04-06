<p class="taxonomy-instructions"><?=lang('create_tree_instructions')?></p>
<?=form_open($_form_base.AMP.'method=update_trees')?>

<?php

	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
								array('data' => lang('option'), 'style' => 'width:200px'),
								array('data' => lang('value'), 'style' => '')
							);

	$this->table->add_row(
	form_hidden('id', '').
	lang('tree_label'),
	form_input('label', set_value('label', ''), 'id="tree_label"')								
	);
	
	$this->table->add_row(
	lang('template_preferences'),
	form_multiselect('template_preferences[]', $templates, '', 'class="taxonomy-multiselect"')
	);
	
	$this->table->add_row(
	lang('channel_preferences'),
	form_multiselect('channel_preferences[]', $channels, '', 'class="taxonomy-multiselect"')	
	);

	if(count($member_groups))
	{
		$this->table->add_row(
		lang('member_preferences'),
		form_multiselect('member_group_preferences[]', $member_groups, '', 'class="taxonomy-multiselect"')	
		);
	}

	echo $this->table->generate();
	$this->table->clear(); // needed to reset the table
?>

<input type="submit" class="submit" value="<?=lang('create_tree')?>" />
<?=form_close()?>