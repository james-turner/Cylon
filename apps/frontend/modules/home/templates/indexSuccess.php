Hello everyone, this is the world! You're currently watching channel id: <?php echo $channelId; ?>

<p>
	<?php if (null !== $boxIP) { ?>
		Your box IP is: <?php echo $boxIP; ?>
	<?php } else { ?>
		You don't have associated box.
	<?php } ?>
</p>

<p>
	<a href="<?php echo url_for('home/post'); ?>">Post to Wall</a>
</p>

<p>
	<a href="<?php echo $logoutUrl;?>">Logout</a>
</p>