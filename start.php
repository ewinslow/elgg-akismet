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
	
	foreach ($fields as $field) {
		akismet_filter($user, $field, $user);
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
		register_error(elgg_echo('akismet:spam'));
		$object->disable('spam');
		$owner->ban('spam');
		
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

register_elgg_event_handler('init', 'system', 'akismet_init');
