<?php

$plugin = $vars['entity'];
$ban_max_days = $plugin->ban_max_days;

if (!$ban_max_days) {
	$ban_max_days = 7;
}
?>

<div>
	<label><?php echo elgg_echo('akismet:api_key'); ?>:</label>
	<?php echo elgg_view('input/text', array('internalname' => 'params[api_key]', 'value' => $plugin->api_key)); ?>
</div>

<div>
	<label><?php echo elgg_echo('akismet:ban_max_days'); ?>:</label>
	<?php echo elgg_view('input/text', array('internalname' => 'params[ban_max_days]', 'value' => $ban_max_days)); ?>
</div>