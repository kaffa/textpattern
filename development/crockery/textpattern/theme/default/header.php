<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo LANG; ?>" lang="<?php echo LANG; ?>" dir="<?php echo gTxt('lang_dir'); ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />

	<title>Txp &#8250; <?php echo htmlspecialchars($sitename); ?> &#8250; <?php echo escape_title($pagetitle); ?></title>

	<link rel="stylesheet" type="text/css" href="theme/default/style.css" />
	<?php echo get_element_style($event);?>

	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/textpattern.js"></script>
	<script type="text/javascript">
	<!--
<?php include_once(txpath.DS.'js/textpattern.js.php'); ?>
	-->
	</script>
</head>
<body>

<table id="pagetop" cellpadding="0" cellspacing="0">
<tr id="branding">
	<td><img src="theme/default/img/textpattern.gif" alt="Textpattern" /></td>
	<td id="navpop"><?php echo navPop(1); ?></td>
</tr>

<?php if (!$bm) include('menu.php'); ?>
</table>
