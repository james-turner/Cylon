<?php if ($boxIP):?>

    <?php if(isset($channels[$channelId]['img'])): ?>
        <img src="http://epgstatic.sky.com/epgdata/1.0/paimage/6/0/<?php echo $channels[$channelId]['img']; ?>" style="float:left"/>
    <?php endif; ?>
    <h2>
        You are currently watching <?php echo $channels[$channelId]['now_playing']; ?>
        on <?php echo $channels[$channelId]['name']; ?>
    </h2>
    <p>
        <a href="<?php echo url_for('home/post'); ?>" class="button large blue">Share on facebook &#187;</a>
    </p>

<?php else:?>
    <h2>Your box is not associated yet.</h2>
	<p>
		<a href="#" class="button large orange">Associate your Sky account &#187;</a>
	</p>

<?php endif; ?>