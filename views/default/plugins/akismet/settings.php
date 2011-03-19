<?php

$plugin = $vars['entity'];

?>

<div>
	<label><?php echo elgg_echo('akismet:wordpress_key'); ?>:</label>
	<?php echo elgg_view('input/text', array('name' => 'params[wordpress_key]', 'value' => $plugin->wordpress_key)); ?>
</div>
