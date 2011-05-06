<script type='text/javascript'>
	// set the taxonomy label from the title
	$(document).ready(function() {
		$('.taxonomy_fetch_title').click(function() 
		{
				var titleval = $('input#title').val();								
				$('#taxonomy_label_<?=$field_id?>').val(titleval);
		});
		
		<?php if($custom_url == '[page_uri]'):?>
			
			// hide the template select
			$('select[name=field_id_<?=$field_id?>[template]]').hide();
			
		<?php endif ?>
		
		<?php if($hide_template_select != 1): ?>
		
			$('input[name=field_id_<?=$field_id?>[use_page_uri]]').change(function () 
			{
			    if ($(this).attr('checked')) {
					// alert('checked');
					$('select[name=field_id_<?=$field_id?>[template]]').hide();
			        return;
			    }
			    // alert('unchecked');
			    $('select[name=field_id_<?=$field_id?>[template]]').show();
			});
					
			$('input#".$this->field_name."_use_page_uri:checked').each( function() 
			{ 
				$('tr#taxonomy_template_select_row_".$this->field_id."').hide();
			});
		
		<?php endif ?>
		
		
		
		
	});
</script>

<div class="taxonomy_table">
<?php
	
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
			array('data' => lang('option'), 'colspan' => '2', 'class' => 'taxonomy-breadcrumbs')
		);
	$this->table->add_row(
			array('data' => lang('node_label').'<span class="taxonomy_fetch_title" title="'.lang('fetch_title').'">+</span>',
			 'style' => 'width: 200px'),
			form_input($field_name.'[label]', htmlspecialchars_decode($label), 'id="taxonomy_label_'.$field_id.'" style="width: 60%;"').
			form_hidden($field_name.'[tree_id]', $tree_id).
			form_hidden($field_name.'[custom_url]', ($hide_template_select) ? '[page_uri]' : '').
			form_hidden($field_name.'[node_id]', $node_id).
			form_hidden($field_name.'[org_parent]', $parent).
			form_hidden($field_name.'[is_root]', $is_root)
		);
		
	if(!$is_root)
	{
		$this->table->add_row(
				lang('select_parent'), 
				$select_parent_dropdown
			);
	}
	
	if(isset($templates) && $hide_template_select != 1)
	{
		$use_page_uri_checkbox = '';
		
		if($enable_pages_mode)
			$use_page_uri_checkbox = form_checkbox($field_name.'[use_page_uri]', 1, ($custom_url == '[page_uri]') ? 1 : 0).' Use Pages URI';
		
	
		$this->table->add_row(
				lang('template'),
				form_dropdown($field_name.'[template]', $templates, $template_path).
				' &nbsp; '.$use_page_uri_checkbox						
			);
	}
	
	

	// custom fields
	if($custom_fields)
	{
		
		$hidden_custom_fields = '';
		
		foreach($custom_fields as $custom_field)
		{
			// echo "<pre>"; print_r($fields_data);echo "</pre>"; 
			
			// does the array key exist, if so grab the value
			$value = ( isset($fields_data[ $custom_field['name'] ]) ) ? $fields_data[$custom_field['name']] : '';
			
			// output our custom fields
			if(isset($custom_field['show_on_publish']) && $custom_field['show_on_publish'] == 1)
			{

				switch($custom_field)
				{
					case($custom_field['type'] == 'text'):
							$custom_field_label = $custom_field['label'].':';
							$custom_field_input = form_input('custom['.$custom_field['name'].']', $value, 'id='.$custom_field['name']);
						break;
					case($custom_field['type'] == 'checkbox'):
							$custom_field_label = '&nbsp;';
							$custom_field_input = form_checkbox('custom['.$custom_field['name'].']', 1, $value).' &nbsp; '.$custom_field['label'];
						break;
					case($custom_field['type'] == 'textarea'):
							$custom_field_label = $custom_field['label'].':';
							$custom_field_input = form_textarea('custom['.$custom_field['name'].']', $value, 'id='.$custom_field['name'].',  style=" height:60px;"');
						break;
				}

				$this->table->add_row(
					$custom_field_label,
					$custom_field_input							
				);
				
			}
			
			else
			{
				$hidden_custom_fields .= form_hidden('custom['.$custom_field['name'].']', $value);
			}
		}
		
	}
	
	
	
	
	
	echo $this->table->generate();
	
	echo (isset($hidden_custom_fields)) ? $hidden_custom_fields : '';
	$this->table->clear(); 
	
?>
</div>