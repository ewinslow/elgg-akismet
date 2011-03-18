<?php

	$wordpress_key = $vars['entity']->wordpress_key;

?>

<p>
	<?php echo elgg_echo('akismet:wordpress_key'); ?>: 
		<?php echo elgg_view('input/text', array('internalname' => 'params[wordpress_key]', 'value' => $wordpress_key)); ?>
</p>
