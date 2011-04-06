<?php
	
	
	if(isset($trees))
	{
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('tree_id'),
			lang('manage_nodes'),
			lang('tree_preferences'),
			lang('delete')
		);
		foreach($trees as $tree)
		{
			$this->table->add_row(
					$tree['id'],
					'<a href="'.$tree['edit_nodes_link'].'">'.$tree['tree_label'].'</a>',
					'<a href="'.$tree['edit_tree_link'].'">'.lang('properties').'</a>',
					'<a href="'.$tree['delete_tree_link'].'" class="delete_tree_confirm">'.lang('delete').'</a>'
				);
		}
		echo $this->table->generate();
	}
	else
	{
		echo lang('no_trees_assigned');
	}

	

?>