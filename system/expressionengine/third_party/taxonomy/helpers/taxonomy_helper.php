<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Checks user has access to a Taxonomy Tree
 *
 * @access	public
 * @param	current member group_id (int)
 * @param	allowed group_ids (string 2|3|4 or array)
 * @return	TRUE / FALSE
 */

function has_access_to_tree($member_group, $allowed_member_groups)
{
	
	// make sure permissions is an array
	$allowed_member_groups = (is_array($allowed_member_groups)) ? $allowed_member_groups : explode('|', $allowed_member_groups);
	
	// check for current member group in allowed group, always grant superadmin access
	if(in_array($member_group, $allowed_member_groups) OR $member_group == 1)
	{
		return TRUE;
	}
	
	return FALSE;

}


/**
 * Simple debugging utilitiy for arrays
 *
 * @access	public
 * @param	array
 * @return	fancy
 */
function debug_array($array) 
{
    echo "<pre>"; print_r($array); echo "</pre>"; 
}


// ----------------------------------------------------------------
	
	
/**
 * Sorts an array
 *
 * Returns TRUE 
 *
 * @access	public
 * @param	int
 * @return	string
 */
function array_sort($array, $on, $order=SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0 && is_array($array)) 
    {
    	foreach ($array as $k => $v) 
    	{
        	if (is_array($v)) 
        	{
            	foreach ($v as $k2 => $v2) 
            	{
                	if ($k2 == $on) 
                	{
                    	$sortable_array[$k] = $v2;
                	}
            	}
        	} 
        	else 
        	{
            	$sortable_array[$k] = $v;
        	}
    	}

        switch ($order) 
        {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) 
        {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}
