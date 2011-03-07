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
			var input_text = ''
			for(var item in serialized) {
				var value = serialized[item];
				// console.log('myVar: ', value);
				input_text += 'id:' + value['item_id'] + ',lft:' + value['left'] + ',rgt:' + value['right'] + '|';
			}
			$('#save-taxonomy input.taxonomy-serialise').val(input_text);
			
			var last_updated = $('#save-taxonomy input[name=last-updated]').val();
			var data = '&last_updated=' + last_updated + '&taxonomy_order=' + input_text + '&tree_id=<?=$tree_id?>';
			
			//start the ajax  
	        $.ajax({  
	           
	            url: "<?=$ajax_update_action?>",
	              
	            //GET method is used  
	            type: "GET",  
	  
	            //pass the data           
	            data: data,       
	              
	            //Do not cache the page  
	            cache: false,

	            error: function () {                
	                 alert('An error occurred!');                 
	            },
	             
	            //success  
	            success: function (html) {                
	                                   
	                    $('ol#taxonomy-list').removeClass('taxonomy_update_underway');
	                    
	                    var msg = html['data'];
	                    
	                    // alert(data);
	                    
	                    // tell the user to refresh
	                    if(msg == 'last_update_mismatch')
	                    {
	                    	$('#taxonomy-wapper').html('<div class="taxonomy-error"><h3>Error: The tree you are sorting is out of date.<br />(Another user editing the tree right now?)</h3><p> Your changes have not been saved to prevent possible damage to the Taxonomy Tree. <br />Please refresh the page to get the latest version.</p></div>');
	                    }
	                    
	                    $('#save-taxonomy input[name=last-updated]').val(html['last_updated']);
	                    
	                    $.ee_notice("Tree order updated", {type: 'success'});
	                                 
	            }         
	        });  

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
			<input type="text" name="tree_id" value="<?=$tree_id?>" />
			<input type="text" class="input taxonomy-serialise" name="taxonomy_order" />
			<input type="text" value="<?=$last_updated?>" class="input last-updated" name="last-updated" />
	</div>
	<div id="taxonomy-output"></div>
</div>