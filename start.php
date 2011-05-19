<?php
/**
 * Akismet plugin for Elgg.
 */

function akismet_init() {
	// Register for autoloading
	elgg_register_class('Akismet', dirname(__FILE__) . "/vendors/akismet/PHP5Akismet.0.4/Akismet.class.php");

	// Only filter if the plugin has been set up
	if (elgg_get_plugin_setting('api_key', 'akismet')) {
		elgg_delete_admin_notice('akismet_key');
		elgg_register_event_handler('create', 'annotation', 'akismet_annotation_handler');
		elgg_register_event_handler('create', 'object', 'akismet_object_handler');
	}
}

function akismet_annotation_handler($event, $object_type, ElggAnnotation $object) {
	if (($object) && ($object->name == 'generic_comment' || $object->name == 'messageboard')) {
		$comment = $object->value;
		$author = "";
		$author_email = "";
		$author_url = "";
		$owner = get_entity($object->owner_guid);
		if ($owner) {
			$author = $owner->name;
			$author_email = $owner->email;
			$author_url = $owner->website;
		}

		if (akismet_scan($comment, $author, $author_email, $author_url)) {
			register_error(elgg_echo('akismet:spam'));

			return false;
		} else {
			system_message(elgg_echo('akismet:ham'));
		}
	}
}

function akismet_object_handler($event, $object_type, ElggObject $object) {
	if ($object) {
		$comment = $object->description;
		$author = "";
		$author_email = "";
		$author_url = "";
		$owner = get_entity($object->owner_guid);

		if ($owner) {
			$author = $owner->name;
			$author_email = $owner->email;
			$author_url = $owner->website;
		}

		if (akismet_scan($comment, $author, $author_email, $author_url)) {
			register_error(elgg_echo('akismet:spam'));
			$object->disable('spam');
		} else {
			system_message(elgg_echo('akismet:ham'));
		}
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
	$key = elgg_get_plugin_setting('api_key', 'akismet');

	if (!$key) {
		throw new ConfigurationException(elgg_echo('akismet:noapikey'));
	}

	$akismet = new Akismet(elgg_get_site_url(), $key);
	$akismet->setCommentAuthor($author);
	$akismet->setCommentAuthorEmail($author_email);
	$akismet->setCommentAuthorURL($author_url);
	$akismet->setCommentContent($comment);
	$akismet->setPermalink($permlink);

	return $akismet->isCommentSpam();
}

elgg_register_event_handler('init', 'system', 'akismet_init');
