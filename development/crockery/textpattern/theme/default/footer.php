

<div id="end_page">
	<?php echo navPop(); ?>

	<p><a href="http://textpattern.com/"><img src="theme/default/img/carver.gif" width="60" height="48" alt="" /></a><br />
		Textpattern	&#183; <?php echo txp_version; ?></p>

<?php if ($txp_user) { ?>
	<p id="moniker"><?php echo gTxt('logged_in_as'); ?> <span><?php echo htmlspecialchars($txp_user); ?></span><br />
	[<a href="index.php?logout=1"><?php echo gTxt('logout'); ?></a>]</p>
<?php } ?>
</div>

</body>
</html>