<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Sky Share</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <meta name="description" content="Sky Share" />
    <meta name="keywords" content="" />

	<!-- CSS -->
	<?php include_stylesheets(); ?>

	<!-- JS -->
    <?php include_javascripts(); ?>
</head>
<body >
<div class="main">
	<div class="header_resize">
		<div class="header">
		  <div class="logo">
			<h1><img src="http://www.studentvalue.co.uk/Unnamed%20site%207/sky-logo.gif" alt="Sky Share" class="logo-img"/></h1>
		  </div>
		  <div class="menu">
			<ul>
			  <li><a href="<?php echo url_for('@homepage'); ?>" class="active"><span> Home </span></a></li>
			  <li><a href="#" ><span> About </span></a></li>
			  <li><a href="#"><span> More </span></a></li>
			</ul>
		  </div>
		</div>
	</div>
	<div class="column">
		<h1 class="title">Sky Share</h1>
		<hr />
		<div class="two-thirds left clear">
			<?php echo $sf_content ?>
		</div>
		<div class="one-third right">
			<h4>You're friends are watching</h4>
			<ul>
				<li>Coronation Street - 500</li>
				<li>Coronation Street - 400</li>
				<li>Coronation Street - 300</li>
				<li>Coronation Street - 200</li>
				<li>Coronation Street - 100</li>
			</ul>
		</div>
		<div class="push"></div>
	</div>
</div>
<div class="footer">
	<div class="footer-content">
		<p class="left">&#169; Copyright <br />
		<a href="#">Home</a> | <a href="#">Contact</a>
	  </p>
	</div>
</div>

</body>
</html>