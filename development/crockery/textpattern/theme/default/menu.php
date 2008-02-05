<tr id="nav-primary">
	<td align="center" class="tabs" colspan="2">
		<table cellpadding="0" cellspacing="0" align="center">
		<tr>
			<td id="messagepane" valign="middle"<?php if (!empty($msgclass)) echo ' class="'.$msgclass.'"'; ?>>&nbsp;<?php echo $message; ?></td>
<?php
	if ($txp_user)
	{
		foreach (areas() as $a => $tabs)
		{
			if ($tabs and has_privs("tab.{$a}"))
			{
				echo areatab(gTxt('tab_'.$a), $a, array_shift($tabs), $area);
			}
		}
	}
?>

			<td class="tabdown"><a href="<?php echo hu; ?>" class="plain" target="_blank"><?php echo gTxt('tab_view_site'); ?></a></td>
		</tr>
		</table>
	</td>
</tr>
<?php if ($txp_user) { ?>

<tr id="nav-secondary">
	<td align="center" class="tabs" colspan="2">
		<table cellpadding="0" cellspacing="0" align="center">
		<tr><?php echo tabsort($area, $event); ?>

		</tr>
		</table>
	</td>
</tr>
<?php } ?>