<div class="taxonomy-content">
	<div class="taxonomy-advanced-settings" style="margin-bottom: -20px;">
	<h3>Field Settings</h3>
	<p>Field groups may be shared across channels, choose which Tree should associated with each channel</p>

<script type="text/javascript">
	$(document).ready(function() {
	
		var channels_count = $('select.[name="options[channel_id][]"] option').size();
	
		$('#taxonomy-add-template-row a').live('click', function() {
			$("#taxonomy-template-row").clone().removeAttr("id").appendTo( $(this).parent().parent() );
			
			var field_settings_count = $('.taxonomy-advanced-settings table').length - 1;
			
			if(field_settings_count == channels_count)
			{
				$('#taxonomy-add-template-row a').hide();
			}
			
			return false;
		});
		
		$('a.taxonomy_pref_delete').live('click', function() {
			$(this).closest('table.mainTable').remove();
			
			var field_settings_count = $('.taxonomy-advanced-settings table').length - 1;
			
			if(field_settings_count < channels_count)
			{
				$('#taxonomy-add-template-row a').show();
			}
			
			
			return false;
		});

	});
</script>

<style type="text/css">
	#taxonomy-template-row {display: none;}
</style>

<div id="taxonomy-template-row">
<?php
	
		$yes_no_options = array(0 => lang('no'), 1 => lang('yes'));
		
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
								array('data' => lang('option'), 'style' => 'width:40%'),
								array('data' => lang('value'), 'style' => '')
							);
							
		$this->table->add_row(
				lang('select_channel').':',
				"<a href='#' class='taxonomy_pref_delete' style='float:right'><img src='".$this->cp->cp_theme_url."images/icon-delete.png' /></a>".
				form_dropdown("options[channel_id][]", $channels, '', 'class="taxonomy_channels"')
			);

		$this->table->add_row(
				lang('select_tree').':',
				form_dropdown("options[tree_id][]", $tree_options)
			);

		$this->table->add_row(
				lang('enable_pages_mode').':',
				form_dropdown("options[enable_pages_mode][]", $yes_no_options)
			);

		$this->table->add_row(
				'&nbsp; <img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" /> '.
				lang('hide_template_select').':',
				form_dropdown("options[hide_template_select][]", $yes_no_options)
			);
			
		echo $this->table->generate();
		$this->table->clear(); 
?>

</div>

<?php
	
	if(isset($data['channel_id']))
	{
		
		foreach($data['channel_id'] as $key => $option)
		{
		
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
								array('data' => lang('option'), 'style' => 'width:40%'),
								array('data' => lang('value'), 'style' => '')
							);
			$this->table->add_row(
					lang('select_channel').':',
					"<a href='#' class='taxonomy_pref_delete' style='float:right'><img src='".$this->cp->cp_theme_url."images/icon-delete.png' /></a>".
					form_dropdown("options[channel_id][$key]", $channels, $data['channel_id'][$key])
				);
	
			$this->table->add_row(
					lang('select_tree').':',
					form_dropdown("options[tree_id][$key]", $tree_options, $data['tree_id'][$key])
				);

			$this->table->add_row(
					lang('enable_pages_mode').':',
					form_dropdown("options[enable_pages_mode][$key]", $yes_no_options, $data['enable_pages_mode'][$key])
				);

			$this->table->add_row(
					'&nbsp; <img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" /> '.
					lang('hide_template_select').':',
					form_dropdown("options[hide_template_select][$key]", $yes_no_options, $data['hide_template_select'][$key])
				);

			echo $this->table->generate();
			$this->table->clear(); 
		}
	}

?>
		<?php if(!isset($channels)): ?>
		<p class="notice">No channels have been configured to use this field group</p>
		<?php else: ?>
		<p id="taxonomy-add-template-row">
			<a href="#"<?php if(isset($data['channel_id']) && count($channels) == count($data['channel_id'])) 
							echo ' class="js_hide"' ?>>Add a Channel Setting [+]</a>
		</p>
		<?php endif ?>
	</div>
</div>