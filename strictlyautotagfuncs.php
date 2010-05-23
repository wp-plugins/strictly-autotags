<?php
/**
Compatibility functions for the strictlyautotags.class.php plugin 
*/

// set up constants used by the AutoTag method
if(!defined('AUTOTAG_BOTH')){
	define('AUTOTAG_BOTH',0);
}
if(!defined('AUTOTAG_SHORT')){
	define('AUTOTAG_SHORT',1);
}


if(!function_exists('ShowDebug')){

	// if the DEBUG constant hasn't been set then create it and turn it off
	if(!defined('DEBUG')){
		define('DEBUG',false);
	}

	/**
	 * function to output debug to page
	 *
	 * @param string $msg
	 */
	function ShowDebug($msg){
		if(DEBUG){
			if(!empty($msg)){
				if(is_array($msg)){
					print_r($msg);
					echo "<br />";
				}else{
					echo htmlspecialchars($msg) . "<br>";
				}
			}
		}
	}
}


if(!function_exists('IsNothing')){

	/**
	 * Checks whether a value is set and not empty
	 *
	 * @param variant $val
	 * @return boolean
	 */
	function IsNothing($val){
		if(isset($val)){
			if(!empty($val)){
				return false;
			}
		}
		return true;		
	}
}
	

// handle any future wordpress update which may or may not remove add_filters and add_actions

if ( !function_exists('add_filters') ) {
	function add_filters($tags, $function_to_add, $priority = 10, $accepted_args = 1) {
		if ( is_array($tags) ) {
			foreach ( (array) $tags as $tag ) {
				add_filter($tag, $function_to_add, $priority, $accepted_args);
			}
			return true;
		} else {
			return add_filter($tags, $function_to_add, $priority, $accepted_args);
		}
	}
}

if ( !function_exists('add_actions') ) {
	function add_actions($tags, $function_to_add, $priority = 10, $accepted_args = 1) {
		return add_filters($tags, $function_to_add, $priority, $accepted_args);
	}
}
?>