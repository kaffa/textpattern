	var cookieEnabled = checkCookies();

	if (!cookieEnabled)
	{
		confirm('<?php echo trim(gTxt('cookies_must_be_enabled')); ?>');
	}

<?php

	if ($event == 'list')
	{
		$sarr = array("\n", '-');
		$rarr = array('', '&#45;');

		$sections = '';

		$rs = safe_column('name', 'txp_section', "name != 'default'");

		if ($rs)
		{
			$sections = str_replace($sarr, $rarr, addslashes(selectInput('Section', $rs, '', true)));
		}

		$category1 = '';
		$category2 = '';

		$rs = tree_get('txp_category', NULL, "type='article'");

		if ($rs)
		{
			$category1 = str_replace($sarr, $rarr, addslashes(treeSelectInput('Category1', $rs, '')));
			$category2 = str_replace($sarr, $rarr, addslashes(treeSelectInput('Category2', $rs, '')));
		}

		$statuses = str_replace($sarr, $rarr, addslashes(selectInput('Status', array(
			1 => gTxt('draft'),
			2 => gTxt('hidden'),
			3 => gTxt('pending'),
			4 => gTxt('live'),
			5 => gTxt('sticky'),
		), '', true)));

		$comments_annotate = addslashes(onoffRadio('Annotate', safe_field('val', 'txp_prefs', "name = 'comments_on_default'")));

		$authors = '';

		$rs = safe_column('name', 'txp_users', "privs not in(0,6)");

		if ($rs)
		{
			$authors = str_replace($sarr, $rarr, addslashes(selectInput('AuthorID', $rs, '', true)));
		}

?>

	function poweredit(elm)
	{
		var something = elm.options[elm.selectedIndex].value;

		// Add another chunk of HTML
		var pjs = document.getElementById('js');

		if (pjs == null)
		{
			var br = document.createElement('br');
			elm.parentNode.appendChild(br);

			pjs = document.createElement('P');
			pjs.setAttribute('id','js');
			elm.parentNode.appendChild(pjs);
		}

		if (pjs.style.display == 'none' || pjs.style.display == '')
		{
			pjs.style.display = 'block';
		}

		if (something != '')
		{
			switch (something)
			{
				case 'changesection':
					var sections = '<?php echo $sections; ?>';
					pjs.innerHTML = '<span><?php echo gTxt('section') ?>: '+sections+'</span>';
				break;

				case 'changecategory1':
					var categories = '<?php echo $category1; ?>';
					pjs.innerHTML = '<span><?php echo gTxt('category1') ?>: '+categories+'</span>';
				break;

				case 'changecategory2':
					var categories = '<?php echo $category2; ?>';
					pjs.innerHTML = '<span><?php echo gTxt('category2') ?>: '+categories+'</span>';
				break;

				case 'changestatus':
					var statuses = '<?php echo $statuses; ?>';
					pjs.innerHTML = '<span><?php echo gTxt('status') ?>: '+statuses+'</span>';
				break;

				case 'changecomments':
					var comments = '<?php echo $comments_annotate; ?>';
					pjs.innerHTML = '<span><?php echo gTxt('comments'); ?>: '+comments+'</span>';
				break;

				case 'changeauthor':
					var authors = '<?php echo $authors; ?>';
					pjs.innerHTML = '<span><?php echo gTxt('author'); ?>: '+authors+'</span>';
				break;

				default:
					pjs.style.display = 'none';
				break;
			}
		}

		return false;
	}

	addEvent(window, 'load', cleanSelects);

<?php } ?>