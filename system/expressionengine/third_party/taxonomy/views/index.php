<script type="text/javascript" src="<?=$_theme_base_url?>js/taxonomy.js"></script>
<?php

	if(isset($trees))
	{
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			array('data' => lang('tree_id'), 'style' => 'width: 25px; text-align: center;')
			,
			lang('tree_label'),
			lang('tree_preferences'),
			lang('delete')
		);
		foreach($trees as $tree)
		{
			$this->table->add_row(
					array('data' => $tree['id'], 'style' => ' text-align: center; text-shadow: 0 1px 0 #fff;  font-weight: bold;font-size: 14px;'),
					array('data' => '<a href="'.$tree['edit_nodes_link'].'">'.$tree['tree_label'].'</a>', 'style' => 'width: 60%;font-size: 14px; font-weight: bold;  text-shadow: 0 1px 0 #fff;'),
					'<a href="'.$tree['edit_tree_link'].'">'.lang('edit_preferences').'</a>',
					array('data' => '<a href="'.$tree['delete_tree_link'].'" class="delete_tree_confirm">
					<img src="'.$this->cp->cp_theme_url.'images/icon-delete.png" /></a>', 'style' => 'width: 20px; text-align: center;')
					
				);
		}
		echo $this->table->generate();
	}
	else
	{
		echo lang('no_trees_assigned');
	}

?>