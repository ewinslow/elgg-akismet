<?php

	/**
	 * Akismet plugin for Elgg.
	 *
	 * @author Marcus Povey <marcus@dushka.co.uk>
	 * @copyright Marcus Povey 2009-2010 
	 * @license GNU Public License version 2
	 */
	
	require_once(dirname(__FILE__) . "/vendors/akismet/PHP5Akismet.0.4/Akismet.class.php");

	function akismet_init()
	{
    	global $CONFIG;
    	
    	// Get settings
    	$CONFIG->wordpress_key = get_plugin_setting('wordpress_key', 'akismet');
		
		
    	// Listen to events
    	register_elgg_event_handler('create', 'annotation', 'akismet_event_handler');
		
	}
	
	function akismet_event_handler($event, $object_type, $object)
	{
		if (($object) && ($object->name == 'generic_comment'))
		{
			$comment = $object->value;
			$author = "";
			$author_email = "";
			$author_url = "";
			$owner = get_entity($object->owner_guid);
			if ($owner)
			{
				$author = $owner->name;
				$author_email = $owner->email;
				$author_url = $owner->website;
			}
			
			if (akismet_scan($comment, $author, $author_email, $author_url))
			{
				register_error(elgg_echo('akismet:spam'));
				
				return false;
			}
			
			system_message(elgg_echo('akismet:ham'));
		}
	}
	
	/**
	 * Test a message to see if it is spam.
	 *
	 * @param unknown_type $comment
	 * @param unknown_type $author
	 * @param unknown_type $author_email
	 * @param unknown_type $author_url
	 * @param unknown_type $permlink
	 * @return true if spam
	 */
	function akismet_scan($comment, $author = "", $author_email = "", $author_url = "", $permlink = "")
	{
		global $CONFIG;
		
		$key = $CONFIG->wordpress_key;
		$url = $CONFIG->wwwroot;

		if (!$key) throw new ConfigurationException(elgg_echo('akismet:nowordpresskey'));
		
		$akismet = new Akismet($url, $key);
		$akismet->setCommentAuthor($author);
		$akismet->setCommentAuthorEmail($author_email);
		$akismet->setCommentAuthorURL($author_url);
		$akismet->setCommentContent($comment);
		$akismet->setPermalink($permlink);

		return $akismet->isCommentSpam();
	}
		
	register_elgg_event_handler('init','system','akismet_init');
?>