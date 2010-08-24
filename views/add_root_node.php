<?php
		echo form_open($add_root_form_action);
		
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			array('data' => "&nbsp;<img src='".ASSET_PATH."gfx/add_node.png' style='margin-right: 5px; vertical-align: bottom;' />&nbsp;".lang('insert_a_root'), 'class' => 'create_node_new', 'style' => 'width:30%'),
				array('data' => "", 'class' => 'create_node_new', 'style' => 'width:70%')
		);
	

		// select node name, and parent node
		$this->table->add_row(
			lang('title'),
			form_hidden('tree', $tree, '').
			// form_hidden('extra', '', '').
			form_input('label', set_value('', ''), 'id="label", style="width: 60%;"')
		);
		
		
		// add properties
		
		$this->table->add_row(
			lang('internal_url'),
			'<div id="taxonomy_select_template" style="display: inline;">'.form_dropdown('template_path', $templates, '').
			" &nbsp; </div><div id='select_entry' style='display: inline;'>".
			form_dropdown('entry_id', $entries, '')."</div>"
		);
		
		
		$this->table->add_row(
			lang('override_url'),
			form_input('custom_url', set_value('', ''), 'id="custom_url", style="width: 60%;"').$select_page_uri
		);
		$this->table->add_row(
			'',
			form_submit(array('name' => 'submit', 'value' => lang('add'), 'class' => 'submit'))
		);
		
		echo $this->table->generate();
		
		$this->table->clear(); // reset the table
		
		print form_close();
				
?>