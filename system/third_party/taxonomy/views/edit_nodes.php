<script type="text/javascript" src="<?=$theme_base?>js/jquery-ui-1.8.2.custom.min.js"></script>
<script type="text/javascript" src="<?=$theme_base?>js/jquery.ui.nestedSortable.js"></script>
<script type="text/javascript" src="<?=$theme_base?>js/jquery.serialize-list.js"></script>
<script type="text/javascript">
	$(document).ready(function(){

		$('ol#taxonomy-list').nestedSortable({	
			disableNesting: 'no-nest',
			forcePlaceholderSize: true,
			handle: 'div.item-handle',
			items: 'li',
			opacity: .95,
			placeholder: 'placeholder',
			tabSize: 25,
			tolerance: 'pointer',
			toleranceElement: '> div'
			
		});
		
		// fix the tree height to prevent any 'jumping'
		$('ol#taxonomy-list').height($('ol#taxonomy-list').height());

		$( "ol#taxonomy-list" ).bind( "sortupdate", function(event, ui) {
		
			$('ol#taxonomy-list').addClass('taxonomy_update_underway');
		
			serialized = $('ol#taxonomy-list').nestedSortable('toArray', {startDepthCount: 1});
			var taxonomy_order = ''
			for(var item in serialized) {
				var value = serialized[item];
				// console.log('myVar: ', value);
				taxonomy_order += 'id:' + value['item_id'] + ',lft:' + value['left'] + ',rgt:' + value['right'] + '|';
			}
			$('#save-taxonomy input.taxonomy-serialise').val(taxonomy_order);
			
			// prep our vars for posting
			var $form = $('#save-taxonomy form'),
				p_XID 			= $form.find( 'input[name="XID"]' ).val(),
		        p_tree_id 		= $form.find( 'input[name="tree_id"]' ).val(),
		        p_taxonomy_order 	= $form.find( 'input[name="taxonomy_order"]' ).val(),
		        p_last_updated 	= $form.find( 'input[name="last-updated"]' ).val(),
		        url = $form.attr( 'action' );
	
		    	// Send the data using post
		    	$.post( url, { 'XID': p_XID, 'tree_id': p_tree_id, 'taxonomy_order': p_taxonomy_order, 'last_updated': p_last_updated},
		      		function( data ) 
		      		{
						
			      		var msg = data.data;
			      		
			      		// flag if there's a date mismatch
			      		if(msg == 'last_update_mismatch')
	                    {
	                    	$('#taxonomy-wapper').html('<div class="taxonomy-error"><h3>Error: The tree you are sorting is out of date.<br />(Another user editing the tree right now?)</h3><p> Your changes have not been saved to prevent possible damage to the Taxonomy Tree. <br />Please refresh the page to get the latest version.</p></div>');
	                    }
	
						// update the timestamp field with response timestamp
			      		$("#save-taxonomy .last-updated").val(data.last_updated);
			      		
			          	// $( "#taxonomy-output" ).html( msg );
			          	
			          	// remove the updator indicator
			          	$('ol#taxonomy-list').removeClass('taxonomy_update_underway');
		          	
		     		}, "json");
		      
		     	
			
				$.ee_notice("Tree order updated", {type: 'success'});

		});

	});
</script>
<div id="taxonomy-wapper">

	<div id="taxonomy-add-node-container"><div class="inset">
		<a href="<?=$_base?>&amp;method=manage_node&amp;tree_id=<?=$tree_id?>" class="add-node close">Add a node</a>
	</div></div>
	
	<div id="taxonomy-list-container">
		<?=$taxonomy_list?>
	</div>
	
	<div id="save-taxonomy">
		<?=form_open($update_action)?>
			<input type="text" name="tree_id" value="<?=$tree_id?>" />
			<input type="text" class="input taxonomy-serialise" value="" name="taxonomy_order" />
			<input type="text" value="<?=$last_updated?>" class="input last-updated" name="last-updated" />
			<!-- <input type="submit" value="submit"> -->
		<?=form_close()?>
	</div>
	<div id="taxonomy-output"></div>
</div>