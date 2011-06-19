<?php
/**
 * Akismet plugin for Elgg.
 */

function akismet_init() {
	require dirname(__FILE__) . "/vendors/akismet/PHP5Akismet.0.4/Akismet.class.php";

	// Only filter if the plugin has been set up and we're not an admin user
	
	if (get_plugin_setting('api_key', 'akismet') && !isadminloggedin()) {
		register_elgg_event_handler('create', 'object', 'akismet_object_handler');
		register_elgg_event_handler('update', 'user', 'akismet_user_handler');
		register_elgg_event_handler('create', 'annotation', 'akismet_annotation_handler');
	}
}

function akismet_user_handler($event, $type, ElggUser $user) {
	$fields = array($user->briefdescription, $user->description);
	
	// we need to check if we're in an action because of updates to last_login, etc
	// akismet doesn't care about that.
	$action = get_input('action');
	$ignored_actions = array(
		'login',
		'logout'
	);
	
	if (!in_array($action, $ignored_actions)) {
		foreach ($fields as $field) {
			akismet_filter($user, $field, $user);
		}
	}
}

function akismet_object_handler($event, $object_type, ElggObject $object) {
	akismet_filter($object, $object->description, $object->getOwnerEntity());
}

function akismet_annotation_handler($event, $type, ElggAnnotation $annotation) {
	akismet_filter($annotation, $annotation->value, $annotation->getOwnerEntity());
}

function akismet_filter($object, $content, $owner) {
	if (akismet_scan($content, $owner->name, $owner->email, $owner->website, $object->getURL())) {
		if ($object instanceof ElggEntity) {
			$object->disable('spam');
		} elseif ($object instanceof ElggAnnotation) {
			akismet_disable_annotation($object);
		} else {
			// don't know how to deal with this type.
			return;
		}
		
		// only disable the user if older than X days
		$ban_max_days = $plugin->ban_max_days;
		
		if (!$ban_max_days) {
			$ban_max_days = 7;
		}
		
		$too_old = strtotime("-$ban_max_days days") - $owner->time_created > 0;
	
		if (!$too_old) {
			register_error(elgg_echo('akismet:spam'));
			$owner->ban('spam');
		} else {
			register_error(elgg_echo('akismet:spam_no_ban'));
		}
		
		//bail on the current action + return to previous page seems to make the most sense here...
		forward(REFERER);
	}
}

/**
 * Test a message to see if it is spam.
 *
 * @param string $comment
 * @param string $author
 * @param string $author_email
 * @param string $author_url
 * @param string $permlink
 *
 * @return bool true if spam
 */
function akismet_scan($comment, $author = "", $author_email = "", $author_url = "", $permlink = "") {
	$key = get_plugin_setting('api_key', 'akismet');

	if (!$key) {
		throw new ConfigurationException(elgg_echo('akismet:noapikey'));
	}

	$site_url = get_config('site')->url;
	$akismet = new Akismet($site_url, $key);
	$akismet->setCommentAuthor($author);
	$akismet->setCommentAuthorEmail($author_email);
	$akismet->setCommentAuthorURL($author_url);
	$akismet->setCommentContent($comment);
	$akismet->setPermalink($permlink);

	return $akismet->isCommentSpam();
}

function akismet_disable_annotation($annotation) {
	$db_prefix = get_config('dbprefix');
	
	$q = "UPDATE {$db_prefix}annotations SET enabled = 'no' WHERE id = '$annotation->id'";
	return update_data($q);
}

register_elgg_event_handler('init', 'system', 'akismet_init');
