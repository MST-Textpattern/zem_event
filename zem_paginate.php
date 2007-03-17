<?php

// -------------------------------------------------------------

	function zem_newer($atts, $thing = false)
	{
		global $thispage, $pretext, $permlink_mode;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		$numPages = $thispage['numPages'];
		$pg				= $thispage['pg'];

		if ($numPages > 1 and $pg > 1)
		{
			$nextpg = ($pg - 1 == 1) ? 0 : ($pg - 1);

			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$parts = array(
				'pg'		 => $nextpg,
				's'			 => @$pretext['s'],
				'c'			 => @$pretext['c'],
				'q'			 => @$pretext['q'],
				'author' => $author
			);

			$parts = $parts + $_GET;
			$url = pagelinkurl($parts);

			if ($thing)
			{
				return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------

	function zem_older($atts, $thing = false)
	{
		global $thispage, $pretext, $permlink_mode;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		$numPages = $thispage['numPages'];
		$pg				= $thispage['pg'];

		if ($numPages > 1 and $pg != $numPages)
		{
			$nextpg = $pg + 1;

			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$parts = array(
				'pg'		 => $nextpg,
				's'			 => @$pretext['s'],
				'c'			 => @$pretext['c'],
				'q'			 => @$pretext['q'],
				'author' => $author
			);

			$parts = $parts + $_GET;
			$url = pagelinkurl($parts);

			if ($thing)
			{
				return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

/*
--- PLUGIN METADATA ---
Name: zem_paginate
Version: 0.1
Type: 0
Description: Pagination tags
Author: Alex Shiels
Link: http://thresholdstate.com/
--- BEGIN PLUGIN HELP ---
	<h1>Textile-formatted help goes here</h1>
--- END PLUGIN HELP & METADATA ---
*/
?>