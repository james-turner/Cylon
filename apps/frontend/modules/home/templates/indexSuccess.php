<h2>
	You are currently watching <?php echo $channels[$channelId]['now_playing']; ?>
	on <?php echo $channels[$channelId]['name']; ?>
</h2>
<p>
	<a href="<?php echo url_for('home/post'); ?>" class="button large blue">Share on facebook &#187;</a>
</p>

<?php /*


<p>
	<?php if (null !== $boxIP) { ?>
		Your box IP is: <?php echo $boxIP; ?>
	<?php } else { ?>
		You don't have associated box.
	<?php } ?>
</p>

<p>
	<a href="<?php echo $logoutUrl;?>">Logout</a>
</p>


<ul>
    <?php foreach($channels as $id => $channel):?>
    <li><a href="/dev.php#<?php echo $id?>"></a><a href="<?php echo url_for('@switch_channel?channelId='.$id) ."#$id"; ?>"><?php echo $channel["name"] ?></a>, Now playing: <?php echo $channel["now_playing"] ?></li>
    <?php endforeach;?>

</ul>
 */ ?>
