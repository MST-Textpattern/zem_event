<?php

/*

Copyright (C) 2006-2007 Alex Shiels http://thresholdstate.com/

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

// maximum number of repeats associated with each recurring event
define('ZEM_MAX_REPEAT_EVENTS', 100);


function zem_event_install() {
	if (!getThings("show tables like '".PFX."zem_event_calendar'"))
		safe_query("create table ".PFX."zem_event_calendar (

			id bigint auto_increment not null primary key,
			article_id bigint not null,

			modified timestamp,
			created timestamp,

			event_date date default null,
			event_time time default null,

			name varchar(255)

			);");

	if (!safe_field('name', 'txp_form', "name='zem_event_display'")) {
		$form = <<<EOF
<txp:zem_event_permlink wraptag="" ><txp:zem_event_name label="Event" wraptag="" /></txp:zem_event_permlink>
<txp:zem_event_date label="Date" wraptag="" />
<txp:zem_event_time label="Time" wraptag="" />
EOF;
		safe_insert('txp_form',
			"name='zem_event_display',
			type='misc',
			Form='".doSlash($form)."'"
		);

	}

	if (!safe_field('name', 'txp_form', "name='zem_event_display_feed'")) {
		$form = <<<EOF
<txp:zem_event_permlink wraptag="" ><txp:zem_event_name label="Event" wraptag="" /></txp:zem_event_permlink>
<txp:zem_event_date label="Date" wraptag="" />
<txp:zem_event_time label="Time" wraptag="" />
EOF;
		safe_insert('txp_form',
			"name='zem_event_display_feed',
			type='misc',
			Form='".doSlash($form)."'"
		);

	}

	if (!safe_field('name', 'txp_form', "name='zem_event_cal_entry'")) {
		$form = <<<EOF
<txp:zem_event_permlink wraptag="" ><txp:zem_event_name label="Event" wraptag="" /></txp:zem_event_permlink>
<txp:zem_event_time label="Time" wraptag="" />
EOF;
		safe_insert('txp_form',
			"name='zem_event_cal_entry',
			type='misc',
			Form='".doSlash($form)."'"
		);

	}

	// locations form
	if (!safe_field('name', 'txp_form', "name = 'zem_event_locations'")) {
			$form = <<<EOF
australia = Australia
canada = Canada
france = France
germany = Germany
span = Spain
EOF;
		safe_insert('txp_form', "name = 'zem_event_locations', type = 'misc', Form = '".doSlash($form)."'");
	}

	// add finish date and time fields
   $cal = getThings('describe '.PFX.'zem_event_calendar');
   if (!in_array('finish_date',$cal))
      safe_alter('zem_event_calendar', 'add finish_date date default null');
   if (!in_array('finish_time',$cal))
      safe_alter('zem_event_calendar', 'add finish_time time default null');
   if (!in_array('location',$cal))
      safe_alter('zem_event_calendar', 'add location varchar(255)');
   if (!in_array('location_url',$cal))
      safe_alter('zem_event_calendar', 'add location_url varchar(255)');
   if (!in_array('url',$cal))
      safe_alter('zem_event_calendar', 'add url varchar(255)');
   if (!in_array('email',$cal))
      safe_alter('zem_event_calendar', 'add email varchar(128)');
   if (!in_array('description',$cal))
      safe_alter('zem_event_calendar', 'add description text');
   if (!in_array('description_html',$cal))
      safe_alter('zem_event_calendar', 'add description_html text');

   if (!in_array('repeat_n',$cal))
      safe_alter('zem_event_calendar', 'add repeat_n int');
   if (!in_array('repeat_period',$cal))
      safe_alter('zem_event_calendar', 'add repeat_period varchar(16)'); // day/week/month/year
   if (!in_array('repeat_to',$cal))
      safe_alter('zem_event_calendar', 'add repeat_to date default null');


	if (!safe_row('id', 'txp_category', "type = 'event' and name = 'root'")) {
		safe_insert('txp_category', "type = 'event', name = 'root', title = 'root'");
	}

	// event m->m category
	if (!getThings("show tables like '".PFX."zem_event_category'"))
		safe_query("create table ".PFX."zem_event_category (
				k1 int not null,
				k2 int not null,
				PRIMARY KEY (k1,k2)
			)"
		);

	if (!getThings("show tables like '".PFX."zem_event_date'")) {
		safe_query("create table ".PFX."zem_event_date (
				event_id bigint not null,
				event_date date not null,
				event_time time default null,
				finish_date date default null,
				finish_time time default null,

				PRIMARY KEY (event_id, event_date)
			)"
		);

		if (in_array('event_date',$cal)) {
			// copy dates and times to the new zem_event_date table
			$rs = safe_rows('id, event_date, event_time, finish_date, finish_time', 'zem_event_calendar', '1=1');
			foreach ($rs as $row) {
				extract($row);
				safe_insert('zem_event_date',
					"event_id='".doSlash($id)."',
					event_date=".($event_date ? "'".doSlash($event_date)."'" : "NULL").",
					event_time=".($event_time ? "'".doSlash($event_time)."'" : "NULL").",
					finish_date=".($finish_date ? "'".doSlash($finish_date)."'" : "NULL").",
					finish_time=".($finish_time ? "'".doSlash($finish_time)."'" : "NULL")
				);
			}
		}

	}

}

function zem_strtotime($t, $now='') {
	if (!trim($t)) return false;
	if (!$now) $now = time();
	$r = @strtotime($t, $now);
	if ($r === -1 or $r === false)
		return false;
	return $r;
}

function zem_tzd() {
	global $gmtoffset;

	$h = $gmtoffset / 3600;
	$m = abs($gmtoffset) % 3600 / 60;

	return sprintf('%+03d:%02d', $h, $m);
}

function zem_strftime($f, $t) {
	$f = preg_replace('/%z/', zem_tzd(), $f);

	return strftime($f, $t);
}

function zem_sqltime($t) {
	return strftime('%H:%M:%S', $t);
}

function zem_sqldate($t) {
	return strftime('%Y-%m-%d', $t);
}

function zem_event_insert_date($event_id, $date, $time=NULL, $finish_date=NULL, $finish_time=NULL) {
	return safe_insert('zem_event_date',
		"event_id = '".doSlash($event_id)."',".
		"event_date='".doSlash(zem_sqldate(zem_strtotime($date)))."',".
		($time ? "event_time='".doSlash(zem_sqltime(zem_strtotime($time)))."'," : 'event_time=NULL,').
		($finish_date ? "finish_date='".doSlash(zem_sqldate(zem_strtotime($finish_date)))."'," : 'finish_date=NULL,').
		($finish_time ? "finish_time='".doSlash(zem_sqltime(zem_strtotime($finish_time)))."'" : 'finish_time=NULL')
	);
}


function zem_event_save($article_id, $name, $date, $time=NULL,$finish_date=NULL,$finish_time=NULL, $location=NULL, $location_url=NULL, $url=NULL, $email=NULL, $description=NULL, $categories=NULL, $repeat_n=NULL, $repeat_period=NULL, $repeat_to=NULL) {

	$d = zem_strtotime($date);
	if (!$d) return false;
	$sql_date = zem_sqldate($d);

	if ($time !== NULL) {
		$t = zem_strtotime($time);
		if (!$t) return false;
		$sql_time = zem_sqltime($t);
	}

	if ($finish_date) {
		$d = zem_strtotime($finish_date);
		if (!$d) return false;
		$sql_fdate = zem_sqldate($d);
	}

	if ($finish_time) {
		$t = zem_strtotime($finish_time);
		if (!$t) return false;
		$sql_ftime = zem_sqltime($t);
	}

	if ($repeat_to) {
		$d = zem_strtotime($repeat_to);
		if (!$d) return false;
		$sql_repeat_to = zem_sqldate($d);
	}

	$description_html = $description;
	if ($description !== NULL) {
		include_once(txpath.'/lib/classTextile.php');
		$textile = new Textile();
		$description_html = $textile->textileThis($description);
	}

	if ($id=safe_field('id', 'zem_event_calendar', "article_id='".doSlash($article_id)."'")) {
		$old = safe_row('*', 'zem_event_calendar', "id='".doSlash($id)."'");
		$result = safe_update('zem_event_calendar',
			"event_date='".doSlash($sql_date)."',".
			($time ? "event_time='".doSlash($sql_time)."'," : 'event_time=NULL,').
			($finish_date ? "finish_date='".doSlash($sql_fdate)."'," : 'finish_date=NULL,').
			($finish_time ? "finish_time='".doSlash($sql_ftime)."'," : 'finish_time=NULL,').
			($location !== NULL ? "location='".doSlash($location)."'," : '').
			($location_url !== NULL ? "location_url='".doSlash($location_url)."'," : '').
			($url !== NULL ? "url='".doSlash($url)."'," : '').
			($email !== NULL ? "email='".doSlash($email)."'," : '').
			($description !== NULL ? "description='".doSlash($description)."'," : '').
			($description_html !== NULL ? "description_html='".doSlash($description_html)."'," : '').
			($repeat_n ? "repeat_n='".intval($repeat_n)."'," : 'repeat_n=NULL,').
			($repeat_period ? "repeat_period='".doSlash($repeat_period)."'," : 'repeat_period=NULL,').
			($repeat_to ? "repeat_to='".doSlash($sql_repeat_to)."'," : 'repeat_to=NULL,').
			"name='".doSlash($name)."',
			modified=now()",
			"id='".doSlash($id)."'"
		);

		// reinsert dates if any have changed
		if ($result and array($old['event_date'], $old['event_time'], $old['finish_date'], $old['finish_time'], $old['repeat_n'], $old['repeat_period'], $old['repeat_to']) != array($sql_date, @$sql_time, @$sql_fdate, @$sql_ftime, @$repeat_n, $repeat_period, $repeat_to)) {
			safe_delete('zem_event_date', "event_id='".doSlash($id)."'");

			zem_event_insert_date($id, $date, $time, $finish_date, $finish_time);
			if ($repeat_n and $repeat_period and $repeat_to) {

				$first_date = zem_strtotime($date);
				$first_finish = zem_strtotime($finish_date);

				$i = 1;
				$count=0;

				while ((++$count < ZEM_MAX_REPEAT_EVENTS) and ($next = zem_strtotime('+'.($i * $repeat_n).' '.$repeat_period, $first_date)) <= zem_strtotime($repeat_to)) {
					zem_event_insert_date($id, zem_sqldate($next), $time, ($finish_date ? zem_sqldate(zem_strtotime('+'.($i * $repeat_n).' '.$repeat_period, $first_finish)) : NULL), $finish_time);

					++$i;
				}

			}
		}
	}
	else {
		$result = safe_insert('zem_event_calendar',
			"event_date='".doSlash($sql_date)."',".
			($time ? "event_time='".doSlash($sql_time)."'," : 'event_time=NULL,').
			($finish_date ? "finish_date='".doSlash($sql_fdate)."'," : 'finish_date=NULL,').
			($finish_time ? "finish_time='".doSlash($sql_ftime)."'," : 'finish_time=NULL,').
			($location !== NULL ? "location='".doSlash($location)."'," : '').
			($location_url !== NULL ? "location_url='".doSlash($location_url)."'," : '').
			($url !== NULL ? "url='".doSlash($url)."'," : '').
			($email !== NULL ? "email='".doSlash($email)."'," : '').
			($description !== NULL ? "description='".doSlash($description)."'," : '').
			($description_html !== NULL ? "description_html='".doSlash($description_html)."'," : '').
			($repeat_n ? "repeat_n='".intval($repeat_n)."'," : 'repeat_n=NULL,').
			($repeat_period ? "repeat_period='".doSlash($repeat_period)."'," : 'repeat_period=NULL,').
			($repeat_to ? "repeat_to='".doSlash($sql_repeat_to)."'," : 'repeat_to=NULL,').
			"name='".doSlash($name)."',
			article_id='".doSlash($article_id)."',
			modified=now(),
			created=now()"
		);
		if ($result)
			$id = $result;

		if ($id) {
			zem_event_insert_date($id, $date, $time, $finish_date, $finish_time);
			if ($repeat_n and $repeat_period and $repeat_to) {

				$first_date = zem_strtotime($date);
				$first_finish = zem_strtotime($finish_date);

				$i = 1;
				$count=0;

				while ((++$count < ZEM_MAX_REPEAT_EVENTS) and ($next = zem_strtotime('+'.($i * $repeat_n).' '.$repeat_period, $first_date)) <= zem_strtotime($repeat_to)) {
					zem_event_insert_date($id, zem_sqldate($next), $time, ($finish_date ? zem_sqldate(zem_strtotime('+'.($i * $repeat_n).' '.$repeat_period, $first_finish)) : NULL), $finish_time);

					++$i;
				}

			}
		}
	}

	if ($result and $categories !== NULL) {
		if (m2m_update_links('zem_event_category', $id, $categories))
			return $id;
		return false;
	}

	if ($result)
		return $id;
	return false;
}

function zem_event_delete($article_id) {
	return safe_delete('zem_event_calendar', "article_id='".doSlash($article_id)."'");
}

function zem_event_date($atts) {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'type'    => 'date',
		'field'   => 'event_date',
		'format'  => '%x',
		'wraptag' => 'span',
		'class'   => __FUNCTION__,
	),$atts, 0);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_time($atts) {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'type'    => 'date',
		'field'   => 'event_time',
		'format'  => '%X',
		'wraptag' => 'span',
		'class'   => __FUNCTION__,
	),$atts, 0);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_name($atts) {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'field'   => 'name',
		'wraptag' => 'span',
		'class'   => __FUNCTION__,
		'default' => 'Untitled',
	),$atts, 0);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_permlink($atts, $thing='') {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'type'    => 'permlink',
		'field'   => 'article_id',
		'wraptag' => '',
		'class'   => __FUNCTION__,
		'linktext'=> NULL,
	),$atts, 0);

	if ($thing)
		$latts['linktext'] = parse($thing);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_finish_date($atts) {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'type'    => 'date',
		'field'   => 'finish_date',
		'format'  => '%x',
		'wraptag' => 'span',
		'class'   => __FUNCTION__,
	),$atts, 0);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_finish_time($atts) {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'type'    => 'date',
		'field'   => 'finish_time',
		'format'  => '%X',
		'wraptag' => 'span',
		'class'   => __FUNCTION__,
	),$atts, 0);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_location($atts) {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'field'   => 'location',
		'wraptag' => 'span',
		'class'   => __FUNCTION__,
	),$atts, 0);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_url($atts, $thing='') {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'type'    => 'link',
		'field'   => 'url',
		'wraptag' => '',
		'class'   => __FUNCTION__,
		'linktext'=> NULL,
	),$atts, 0);

	if ($thing)
		$latts['linktext'] = parse($thing);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_location_url($atts, $thing='') {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'type'    => 'link',
		'field'   => 'location_url',
		'wraptag' => '',
		'class'   => __FUNCTION__,
		'linktext'=> NULL,
	),$atts, 0);

	if ($thing)
		$latts['linktext'] = parse($thing);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_description($atts) {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'field'   => 'description_html',
		'wraptag' => 'div',
		'escape'  => 0,
		'class'   => __FUNCTION__,
	),$atts, 0);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_email($atts) {
	global $zem_thiseventcal;

	$latts = lAtts(array(
		'type'    => 'email',
		'field'   => 'email',
		'wraptag' => '',
		'class'   => __FUNCTION__,
	),$atts, 0);

	return zem_data_field($zem_thiseventcal, $latts + $atts);
}

function zem_event_categories($atts) {
	global $zem_thiseventcal, $pretext;

	$latts = lAtts(array(
		'type'    => 'email',
		'field'   => 'categories',
		'wraptag' => '',
		'class'   => __FUNCTION__,
		'break'   => '',
		'breakclass' => '',
		'link'    => 1,
		'section' => @$pretext['s'],
	),$atts, 0);

	extract($latts);

	if (!isset($zem_thiseventcal['categories']))
		$zem_thiseventcal['categories'] = m2m_linked('zem_event_category', $zem_thiseventcal['id'], 'txp_category');

	$out = array();
	foreach ($zem_thiseventcal['categories'] as $cat) {
		if ($link)
			$out[] = href($cat['title'], pagelinkurl(array('s'=>$section, 'c'=>$cat['name'])));
		else
			$out[] = $cat['title'];
	}

	return doWrap($out, $wraptag, $break, $class, $breakclass);
}

function zem_data_field(&$obj, $atts) {

	extract(lAtts(array(
		'type' => '',
		'field' => '',
		'wraptag' => '',
		'class' => 'zem_data_field',
		'format' => '',
		'title'  => '',
		'title_format' => '',
		'label' => '',
		'sep'   => ': ',
		'escape' => '1',
		'linktext' => NULL,
		'default' => '',
	), $atts, 0));

	$attr = '';

	if (!array_key_exists($field, $obj)) {
		trigger_error(gTxt('unknown_field', array('{field}' => $field)));
	}

	if (empty($obj[$field])) {
		if ($default)
			$out = $default;
		else
			return '';
	}
	else
		$out = $obj[$field];

	if ($type == 'date' and $format)
		$out = zem_strftime($format, strtotime($out));
	if ($type == 'date' and $title_format)
		$attr = ' title="'.zem_strftime($title_format, strtotime($out)).'"';

	if ($escape)
		$out = escape_output($out);

	if ($type == 'permlink')
		$out = permlink(array('class'=>$class,'id'=>$out),$linktext);
	elseif ($type == 'link')
		$out = href($linktext, $out);
	elseif ($type == 'email')
		$out = eE($out);

	$pre = '';

	if ($label)
		$pre = $label . $sep;

	return doTag($pre . $out, $wraptag, $class, $attr);
}


function zem_event_timeq($date_from, $date_to) {
	$d_from = zem_strtotime($date_from);
	if (trim($date_from) and !$d_from)
		trigger_error(zem_event_gTxt('invalid_date', array('{date}' => $date_from)), E_USER_WARNING);
	$d_to = zem_strtotime($date_to);
	if (trim($date_to) and !$d_to)
		trigger_error(zem_event_gTxt('invalid_date', array('{date}' => $date_to)), E_USER_WARNING);

	$w = array();
	if ($d_from) {
		$w[] = "(zem_event_date.event_date >= '".doSlash(zem_sqldate($d_from))."'"
			." or zem_event_date.finish_date >= '".doSlash(zem_sqldate($d_from))."')";
	}

	if ($d_to) {
		$w[] = "(zem_event_date.event_date <= '".doSlash(zem_sqldate($d_to))."'"
			." or zem_event_date.finish_date <= '".doSlash(zem_sqldate($d_to))."')";
	}

	return $w;
}


// list all events
function zem_event_list($atts, $thing=NULL) {
	global $zem_thiseventcal, $pretext;

	extract(lAtts(array(
		'wraptag' => '',
		'class'   => __FUNCTION__,
		'break'   => '',
		'breakclass' => '',
		'form'    => 'zem_event_display',
		'sort'    => 'zem_event_date.event_date asc, zem_event_date.event_time asc',
		'date_from' => (gps('date_from') ? gps('date_from') : 'today'),
		'date_to'   => (gps('date_to') ? gps('date_to') : ''),
		'date'    => gps('date'),
		'label'   => '',
		'labeltag' => '',
		'limit'    => '',
		'category' => (gps('c') ? gps('c') : @$pretext['c']),
		'all_categories' => gps('all_categories'),
		'location' => gps('location'),
		'all_locations' => gps('all_locations'),
	),$atts));

	if ($thing === NULL)
		$thing = fetch_form($form);

	$where = 'zem_event_calendar.id=zem_event_date.event_id and zem_event_calendar.article_id = textpattern.ID and textpattern.Status >= 4 and textpattern.Posted <= now()';

	if ($date) {
		@list($y, $m, $d) = explode('-', $date);
		if ($y and $m and $d) {
			$date_from = $date_to = "$y-$m-$d";
		}
		elseif ($y and $m) {
			$date_from = "$y-$m-01";
			$date_to = strftime('%Y-%m-%d', strtotime('-1 day', strtotime('+1 month', strtotime($date_from))));
		}
		elseif ($y) {
			$date_from = "$y-01-01";
			$date_to = "$y-12-31";
		}
		elseif ($t = zem_strtotime($date)) {
			$date_from = strftime('%Y-%m-%d', $t);
			$date_to = strftime('%Y-%m-%d', $t);
		}
	}

	$w = zem_event_timeq($date_from, $date_to);
	if ($w)
		$where .= (' and '.join(' and ', $w));

	if ($location and !$all_locations) {
		// location could be an array if it came from gps()
		if (is_array($location))
			$locs = $location;
		else
			$locs = do_list($location);
		$where .= (" and location IN (".join(',', quote_list($locs)).")");
	}

	if ($q = gps('q'))
		$where .= (" and (name rlike '".doSlash($q)."' or description rlike '".doSlash($q)."')");

	if ($category and !$all_categories) {
		// category could be an array if it came from gps()
		if (is_array($category))
			$cats = $category;
		else
			$cats = do_list($category);
		$cats_id = safe_column('id', 'txp_category', "type='event' and name IN (".join(',', quote_list($cats)).")");
		if (!$cats_id)
			$cats_id = array(0);
		$where = "zem_event_calendar.id=zem_event_category.k1 and zem_event_category.k2 IN (".join(',', quote_list($cats_id)).") and ".$where;
		$grand_total = safe_count('zem_event_calendar, zem_event_date, textpattern, zem_event_category', $where.' group by zem_event_calendar.id order by '.$sort);
		$lim = zem_event_paginate($limit, $grand_total);
		$rs = safe_rows_start('zem_event_calendar.*, zem_event_date.*, textpattern.*, unix_timestamp(Posted) as uPosted', 'zem_event_calendar, zem_event_date, textpattern, zem_event_category', $where.' group by zem_event_calendar.id order by '.$sort.$lim);
	}
	else {
		$grand_total = safe_count('zem_event_calendar, zem_event_date, textpattern', $where.' order by '.$sort);
		$lim = zem_event_paginate($limit, $grand_total);
		$rs = safe_rows_start('*, unix_timestamp(Posted) as uPosted', 'zem_event_calendar, zem_event_date, textpattern', $where.' order by '.$sort.$lim);
	}

	$out = array();
	while ($row = nextRow($rs)) {
		article_push();
		$zem_thiseventcal = $row;
		populateArticleData($row);
		$out[] = parse($thing);
		$zem_thiseventcal = NULL;
		article_pop();
	}

	return doTag($label, $labeltag, $class).
		doWrap($out, $wraptag, $break, $class, $breakclass);
}

// show the current article's event, if any
function zem_article_event($atts, $thing=NULL) {
	global $thisarticle, $zem_thiseventcal;

	extract(lAtts(array(
		'wraptag' => '',
		'break'   => '',
		'class'   => __FUNCTION__,
		'breakclass' => '',
		'label'   => '',
		'labeltag' => '',
		'form'    => 'zem_event_display',
		'limit'   => '',
	),$atts));

	if ($thing === NULL)
		$thing = fetch_form($form);

	$where = "article_id='".doSlash($thisarticle['thisid'])."' and zem_event_calendar.id=zem_event_date.event_id";

	$lim = '';
	if (intval($limit))
		$lim = ' limit '.intval($limit);

	$rs = safe_rows_start('*', 'zem_event_calendar, zem_event_date', $where.$lim);

	$out = array();
	while ($row = nextRow($rs)) {
		$zem_thiseventcal = $row;
		$out[] = parse($thing);
		$zem_thiseventcal = NULL;

	}

	if ($out)
		return doTag($label, $labeltag, $class).
			doWrap($out, $wraptag, $break, $class, $breakclass);
}


// calendar view
function zem_event_calendar($atts) {

	extract(lAtts(array(
		'wraptag'     => 'ul',
		'break'       => 'li',

		'table'       => 'table',
		'tr'          => 'tr',
		'td'          => 'td',
		'th'          => 'th',
		'caption'     => 'caption',
		'col'         => 'col',
		'colgroup'    => 'colgroup',
		'thead'       => 'thead',
		'tbody'       => 'tbody',
		'cellspacing' => '',

		'date'       => gps('date'),
		'class'       => __FUNCTION__,
		'class_row_num' => 'number',
		'class_row_day' => 'day',
		'class_event' => '', // class used on td element for days with events
		'class_empty' => '', // days with no events
		'class_noday' => '', // spacer cells that aren't real days
		'form'        => 'zem_event_cal_entry',
		'labeltag'    => 'h3',
	),$atts));

	$y = strftime('%Y');
	$m = strftime('%m');
	if ($date) {
		@list($y, $m, $d) = explode('-', $date);
		if ($y and $m) {
		}
		elseif ($y and !$m) {
			$m = strftime('%m');
		}
		elseif ($t = zem_strtotime($date)) {
			$y = strftime('%Y', $t);
			$m = strftime('%m', $t);
		}
	}

	// day number of the first of the month (Sunday = 0)
	$first = mktime(0,0,0,$m, 1, $y);
	$firstday = strftime('%w', $first);
	// number of days in the month
	$numdays = strftime('%d',strtotime('-1 day', strtotime('+1 month', $first)));

	$out = array();

	# caption
	$out[] = doTag(strftime('%B %Y', $first), 'caption');

	# column groups
	$row = array();
	for ($d=1; $d<=7; $d++)
		$row[] = doTag('', 'col', strftime('%a', mktime(0,0,0,$m, ($d+7-$firstday), $y)));
	$out[] = doTag(n.join(n, $row).n, 'colgroup');

	# table headings
	$row = array();
	for ($d=1; $d<=7; $d++)
		$row[] = doTag(strftime('%a', mktime(0,0,0,$m, ($d+7-$firstday), $y)), $th, '', ' scope="col"');
	$out[] = doTag(n.tr(n.join(n, $row).n).n, 'thead');

	$body = array();

	$numrows = ceil(($numdays + $firstday)/7);

	# display each cell in the calendar, 7 x 5 grid
	for ($w=0; $w<$numrows; $w++) {
		$num_row = array();
		$day_row = array();
		for ($d=1; $d<=7; $d++) {
			$daynum = ($w*7) + $d;
			$dayofmonth = $daynum - $firstday;

			if (checkdate($m, $dayofmonth, $y)) {
				// this is a real day
				# need to list the events here
				$events = zem_event_list(array(
					'wraptag'  => $wraptag,
					'break'    => $break,
					'form'     => $form,
					'date'     => "$y-$m-$dayofmonth",
				));
				if ($events) {
					$num_row[] = doTag($dayofmonth, $td, $class_event);
					$day_row[] = doTag($events, $td, $class_event);
				}
				else {
					$num_row[] = doTag($dayofmonth, $td, $class_empty);
					$day_row[] = doTag('&nbsp;', $td, $class_empty);
				}
			}
			else {
				// just a blank to fill in the grid
				$num_row[] = doTag('&nbsp;', $td, $class_noday);
				$day_row[] = doTag('&nbsp;', $td, $class_noday);
			}


		}
		$body[] = doTag(n.join(n, $num_row).n, $tr, $class_row_num);
		$body[] = doTag(n.join(n, $day_row).n, $tr, $class_row_day);
	}

	$out[] = doTag(n.join(n, $body).n, 'tbody');

	return doTag(n.join(n, $out).n, $table, $class, ($cellspacing === '' ? '' : ' cellspacing="'.$cellspacing.'"'));
}

function zem_event_calendar_nav($atts) {
	global $pretext;

	extract(lAtts(array(
		'wraptag' => 'div',
		'break'   => '',
		'class'   => __FUNCTION__,
		'prev'    => '&larr;',
		'prevclass' => 'prev',
		'next'    => '&rarr;',
		'nextclass' => 'next',
		'date'   => gps('date'),
		'labeltag'=> 'h3',
	),$atts, 0));

	$y = ''; $m = '';
	if ($date)
		@list($y, $m, $d) = split('-', $date);
	if (!is_numeric($y))
		$y = strftime('%Y');
	if (!is_numeric($m))
		$m = strftime('%m');

	# next and previous months
	$prev_m = strftime('%Y-%m', strtotime('-1 month', mktime(0,0,0,$m,1,$y)));
	$next_m = strftime('%Y-%m', strtotime('+1 month', mktime(0,0,0,$m,1,$y)));

	# next link
	$out[] = '<a rel="next" class="'.$nextclass.'" href="'.pagelinkurl(array(
		'date' => $next_m,
		's'    =>@$pretext['s'],
		'c'    =>@$pretext['c'],
		'q'    =>@$pretext['q'],
	)).'">'.$next.'</a>';

	# month name
	$out[] = doTag(strftime('%B', mktime(0,0,0,$m,1,$y)), $labeltag, $class);

	# prev link
	$out[] = '<a rel="prev" class="'.$prevclass.'" href="'.pagelinkurl(array(
		'date' => $prev_m,
		's'    =>@$pretext['s'],
		'c'    =>@$pretext['c'],
		'q'    =>@$pretext['q'],
	)).'">'.$prev.'</a>';

	return doWrap($out, $wraptag, $break, $class);
}

function zem_event_mini_calendar($atts) {
	// a mini calendar intended as a navigation control for sidebars etc
	global $pretext;

	extract(lAtts(array(
		'table'       => 'table',
		'tr'          => 'tr',
		'td'          => 'td',
		'th'          => 'th',
		'caption'     => 'caption',
		'col'         => 'col',
		'colgroup'    => 'colgroup',
		'thead'       => 'thead',
		'tbody'       => 'tbody',
		'cellspacing' => '',

		'date'       => gps('date'),
		'class'       => __FUNCTION__,
		'class_row_num' => 'number',
		'class_row_day' => 'day',
		'class_event' => '', // class used on td element for days with events
		'class_empty' => '', // days with no events
		'class_noday' => '', // spacer cells that aren't real days
		'class_link'  => '',
		'labeltag'    => 'h3',
	),$atts));

	$y = ''; $m = '';
	if ($date)
		@list($y, $m, $d) = split('-', $date);
	if (!is_numeric($y))
		$y = strftime('%Y');
	if (!is_numeric($m))
		$m = strftime('%m');

	// day number of the first of the month (Sunday = 0)
	$first = mktime(0,0,0,$m, 1, $y);
	$firstday = strftime('%w', $first);
	// number of days in the month
	$numdays = strftime('%d',strtotime('-1 day', strtotime('+1 month', $first)));

	$out = array();

	# caption
	$out[] = doTag(strftime('%B %Y', $first), 'caption');

	# column groups
	$row = array();
	for ($d=1; $d<=7; $d++)
		$row[] = doTag('', 'col', strftime('%a', mktime(0,0,0,$m, ($d+7-$firstday), $y)));
	$out[] = doTag(n.join(n, $row).n, 'colgroup');

	# table headings
	$row = array();
	for ($d=1; $d<=7; $d++)
		$row[] = doTag(strftime('%a', mktime(0,0,0,$m, ($d+7-$firstday), $y)), $th, '', ' scope="col"');
	$out[] = doTag(n.tr(n.join(n, $row).n).n, 'thead');

	$body = array();

	$numrows = ceil(($numdays + $firstday)/7);

	$days = array();
	$w = zem_event_timeq("$y-$m-01", "$y-$m-$numdays");
	$rs = safe_rows('zem_event_date.*', 'zem_event_calendar,zem_event_date,textpattern', 'zem_event_calendar.id=zem_event_date.event_id and zem_event_calendar.article_id = textpattern.ID and textpattern.Status >= 4 and textpattern.Posted <= now()');
	foreach ($rs as $r) {
		$days[$r['event_date']] = true;
	}

	# display each cell in the calendar, 7 x 5 grid
	for ($w=0; $w<$numrows; $w++) {
		$day_row = array();
		for ($d=1; $d<=7; $d++) {
			$daynum = ($w*7) + $d;
			$dayofmonth = $daynum - $firstday;

			if (checkdate($m, $dayofmonth, $y)) {
				// this is a real day
				if (isset($days["$y-$m-$dayofmonth"])) {
					$url = '<a class="'.$class_link.'" href="'.pagelinkurl(array(
						'date' => "$y-$m-$dayofmonth",
						's'    =>@$pretext['s'],
						'c'    =>@$pretext['c'],
						'q'    =>@$pretext['q'],
					)).'">'.$dayofmonth.'</a>';

					$day_row[] = doTag($url, $td, $class_event);
				}
				else {
					$day_row[] = doTag($dayofmonth, $td, $class_empty);
				}
			}
			else {
				// just a blank to fill in the grid
				$day_row[] = doTag('&nbsp;', $td, $class_noday);
			}


		}
		$body[] = doTag(n.join(n, $day_row).n, $tr, $class_row_day);
	}

	$out[] = doTag(n.join(n, $body).n, 'tbody');

	return doTag(n.join(n, $out).n, $table, $class, ($cellspacing === '' ? '' : ' cellspacing="'.$cellspacing.'"'));
}

function zem_event_search_input($atts) {
	global $pretext, $zem_event_has_js;
	extract(lAtts(array(
		'class' => __FUNCTION__,
		'break' => 'br',
		'wraptag' => 'div',
		'class' => __FUNCTION__,
		'breakclass' => '',
		'sep' => '&nbsp;',
		'method' => 'post',
		'section' => @$pretext['s'],
	), $atts));

	$out[] =
		'<label for="date_from">From:</label>'.$sep.'<input type="text" name="date_from" id="date_from" class="zem_date_select" value="'.htmlspecialchars(gps('date_from')).'" />';
	$out[] =
		'<label for="date_to">To:</label>'.$sep.'<input type="text" name="date_to" id="date_to" class="zem_date_select" value="'.htmlspecialchars(gps('date_to')).'" />';

	$out[] =
		'<label for="q">Search:</label>'.$sep.'<input type="text" name="q" id="q" value="'.htmlspecialchars(gps('q')).'" />';

	$cats = getTree('root', 'event');

	$fs_c = '<legend>Category</legend>';
	$fs_c .= '<input type="checkbox" name="all_categories" id="all_categories" value="1"'.(gps('all_categories') ? ' checked="checked"' : '').' />'.$sep
		.'<label for="all_categories">All Categories</label>'.br.n;

	$gps_c = (gps('c') ? gps('c') : array());

	foreach ($cats as $c)
		$fs_c .= '<input type="checkbox" name="c['.$c['name'].']" id="c['.$c['name'].']" value="'.$c['name'].'"'.(in_array($c['name'], $gps_c) ? ' checked="checked"' : '').' />'.$sep.
			'<label for="c['.$c['name'].']">'.htmlspecialchars($c['title']).'</label>'.br.n;

	$out[] = '<fieldset id="category">'.$fs_c.'</fieldset>';

	$locs = zem_event_available_locations();

	$fs_l = '<legend>Location</legend>';
	$fs_l .= '<input type="checkbox" name="all_locations" id="all_locations" value="1"'.(gps('all_locations') ? ' checked="checked"' : '').' />'.$sep
		.'<label for="all_locations">All Locations</label>'.br.n;

	$gps_l = (gps('location') ? gps('location') : array());

	foreach ($locs as $l)
		$fs_l .= '<input type="checkbox" name="location['.$l.']" id="location['.$l.']" value="'.$l.'"'.(in_array($l, $gps_l) ? ' checked="checked"' : '').' />'.$sep.
			'<label for="location['.$l.']">'.htmlspecialchars($l).'</label>'.br.n;

	$out[] = '<fieldset id="location">'.$fs_l.'</fieldset>';

	$out[] = '<input type="submit" name="search" value="Search" />';

	$js = '';
	if ($zem_event_has_js) {
		if (zem_event_date_format() == 'MM/dd/yyyy') {
			$format = 'mdy';
			$ds = '/';
		}
		else {
			$format = 'ymd';
			$ds = '-';
		}

		$js = script_js(
		'$.datePicker.setDateFormat(\''.$format.'\',\''.$ds.'\');
	$(\'input.zem_date_select\').datePicker();'
		);
	}


	$url = pagelinkurl(array('s'=>$section));
	return '<form action="'.$url.'" method="post">'.doWrap($out, $wraptag, $break, $class, $breakclass).'</form>'.n.$js;
}


function zem_event_head_js($atts) {
	global $zem_event_has_js;

	$zem_event_has_js = true;

	$out = '<link rel="stylesheet" type="text/css" href="'.zem_file_url('datePicker.css').'" />'.n;

	$out .= '<script type="text/javascript" src="'.hu.'textpattern/js/jquery.js"></script>'.n;
	$out .= '<script type="text/javascript" src="'.hu.'textpattern/js/datePicker.js"></script>'.n;

	return $out;
}


function zem_event_paginate($lim, $grand_total, $offset=0, $pageby=0) {

	if (!$grand_total)
		return '';

	$limit = (intval($lim) ? intval($lim) : intval($grand_total));

    if (!$pageby)
	    $pageby = $limit;
    $pg = gps('pg');

    $total = $grand_total - $offset;
    $numPages = intval(ceil($total/$pageby));
    $pg = (!$pg) ? 1 : $pg;
    $pgoffset = $offset + (($pg - 1) * $pageby);
    // send paging info to txp:newer and txp:older
    $pageout['pg']       = $pg;
    $pageout['numPages'] = $numPages;
    $pageout['grand_total'] = $grand_total;
    $pageout['total']    = $total;

	global $thispage;
	if (empty($thispage))
		$thispage = $pageout;

	if ($lim)
		return ' limit '.intval($pgoffset).','.intval($lim);

	return '';
}


// ---- admin side

function zem_toggle_display($id, $linktext, $body, $display=0) {
	$style = ($display ? 'display:block;' : 'display:none;');
	return
		'<h3 class="plain"><a href="#" onclick="toggleDisplay(\''.$id.'\'); return false;">'.gTxt($linktext).'</a></h3>'.
		'<div id="'.$id.'" style="'.$style.'">'.$body.'</div>';
}

function zem_event_date_format() {
	// work out if the locale's preferred date format is m/d/y or something else
	$d = strftime('%x', mktime(0,0,0,1,2,2000));
	if ($d == '01/02/2000') return 'MM/dd/yyyy';
	return 'yyyy-MM-dd';
}


function zem_event_cat_tab($event, $step) {
	switch ($step) {
		case 'create':
			zem_event_cat_tab_create();
		break;

		case 'edit':
			zem_event_cat_tab_edit();
		break;

		case 'save':
			zem_event_cat_tab_save();
		break;

		case 'multi':
			zem_event_cat_tab_multiedit();
		break;

		case 'list':
		default:
			zem_event_cat_tab_list();
		break;
	}
}

function zem_event_cat_tab_list($message = '') {
	pagetop(zem_event_gTxt('event_categories'), $message);

	echo n.n.'<table cellspacing="20" align="center">'.
		n.'<tr>'.
		n.t.'<td style="padding: 8px; vertical-align: top;" class="categories">'.

		n.n.hed(zem_event_gTxt('event_categories'), 3).

		n.form(
			fInput('text', 'title', '', 'edit', '', '', 20).
			fInput('submit', '', gTxt('Create'), 'smallerbox').
			eInput('zem_event_cats').
			sInput('create')
		);

		$rs = getTree('root', 'event');

		if ($rs)
		{
			$items = array();

			foreach ($rs as $a)
			{
				extract($a);

				$items[] = graf(
					checkbox('selected[]', $id, 0).sp.str_repeat(sp.sp, $level * 2).
					eLink('zem_event_cats', 'edit', 'id', $id, $title)
				);
			}

			if ($items)
			{
				zem_event_cat_tab_multiedit_form($items);
			}
		}

		else
		{
			echo n.graf(gTxt('no_categories_exist'));
		}

	echo n.t.'</td>'.
		n.'</tr>'.
		n.endTable();
}

function zem_event_cat_tab_create() {
	$title = ps('title');

	$name = sanitizeForUrl($title);

	$name_sql = doSlash($name);

	if (!$name) {
		$message = zem_event_gTxt('category_invalid', array('{name}' => $name));

		return zem_event_cat_tab_list($message);
	}

	$exists = safe_field('name', 'txp_category', "name = '$name_sql' and type = 'event'");

	if ($exists) {
		$message = zem_event_gTxt('category_already_exists', array('{name}' => $name));

		return zem_event_cat_tab_list($message);
	}

	$q = safe_insert('txp_category', "parent = 'root', type = 'event', name = '$name_sql', title = '".doSlash($title)."'");

	if ($q) {
		if (function_exists('rebuild_tree_full')) {
			rebuild_tree_full('event');
		} else {
			rebuild_tree('root', 1, 'event');
		}

		$message = zem_event_gTxt('category_created', array('{name}' => $name));

		zem_event_cat_tab_list($message);
	}
}

function zem_event_cat_tab_edit() {
	pagetop(gTxt('categories'));

	$id     = assert_int(gps('id'));
	$parent = doSlash(gps('parent'));

	$row = safe_row('*', 'txp_category', "id = $id");

	if ($row) {
		extract($row);

		$out = stackRows(
			fLabelCell(zem_event_gTxt('category_name')).
			fInputCell('name', $name, 1, 20),

			fLabelCell('parent').
			td(zem_event_cat_parent_pop($parent, 'event', $id)),

			fLabelCell(zem_event_gTxt('category_title')).
			fInputCell('title', $title, 1, 30),
			hInput('id', $id),

			tdcs(fInput('submit', '', gTxt('save_button'), 'smallerbox'), 2)
		);
	}

	echo form(
		startTable('edit').
		$out.
		eInput('zem_event_cats').
		sInput('save' ).
		hInput('old_name', $name).
		endTable()
	);
}

function zem_event_cat_tab_save() {
	global $txpcfg;

	extract(doSlash(psa(array('id', 'name', 'old_name', 'parent', 'title'))));

	$id = assert_int($id);

	$name = sanitizeForUrl($name);

	// make sure the name is valid
	if (!$name) {
		$message = zem_event_gTxt('category_invalid', array('{name}' => $name));

		return zem_event_cat_tab_list($message);
	}

	// don't allow rename to clobber an existing category
	$existing_id = safe_field('id', 'txp_category', "type = 'event' and name = '$name'");

	if ($existing_id and $existing_id != $id) {
		$message = zem_event_gTxt('category_already_exists', array('{name}' => $name));

		return zem_event_cat_tab_list($message);
	}

	$parent = ($parent) ? $parent : 'root';

	if (safe_update('txp_category', "parent = '$parent', name = '$name', title = '$title'", "id = $id")) {
		safe_update('txp_category', "parent = '$name'", "parent = '$old_name'");
	}

	if (function_exists('rebuild_tree_full')) {
		rebuild_tree_full('event');
	} else {
		rebuild_tree('root', 1, 'event');
	}

	$message = zem_event_gTxt('category_updated', array('{name}' => doStrip($name)));

	zem_event_cat_tab_list($message);
}

function zem_event_cat_tab_multiedit() {
	$method = ps('edit_method');
	$things = ps('selected');

	if ($things) {
		foreach ($things as $catid) {
			$catid = assert_int($catid);

			if ($method == 'delete') {
				$catname = safe_field('name', 'txp_category', "id = $catid");

				if (safe_delete('txp_category', "id = $catid")) {
					if ($catname) {
						safe_update('txp_category', "parent = 'root'", "type = 'event' and parent = '".doSlash($catname)."'");
					}

					$categories[] = $catid;
				}
			}
		}

		if (function_exists('rebuild_tree_full')) {
			rebuild_tree_full('event');
		} else {
			rebuild_tree('root', 1, 'event');
		}

		$message = zem_event_gTxt('categories_deleted', array('{list}' => join(', ', $categories)));

		zem_event_cat_tab_list($message);
	}
}

function zem_event_cat_parent_pop($name, $type, $id) {
	if ($id) {
		$id = assert_int($id);
		list($lft, $rgt) = array_values(safe_row('lft, rgt', 'txp_category', 'id = '.$id));

		$rs = getTree('root', $type, "lft not between $lft and $rgt");
	} else {
		$rs = getTree('root', $type);
	}

	if ($rs) {
		return treeSelectInput('parent', $rs, $name);
	}

	return gTxt('no_other_categories_exist');
}

function zem_event_cat_tab_multiedit_form($items) {
	$methods = array(
		'delete' => gTxt('delete')
	);

	echo n.form(
		join('', $items).

		n.eInput('zem_event_cats').
		n.sInput('multi').

		small(gTxt('with_selected')).sp.selectInput('edit_method', $methods, '', 1).sp.
			fInput('submit', '', gTxt('go'), 'smallerbox')

	, 'margin-top: 1em', "verify('".gTxt('are_you_sure')."')");
}

function zem_event_datepicker_css() {

	$icon = zem_file_url('zem_event_cal.gif');

	$css = <<<EOF
input.zem_date_select {
	margin-right: 5px;
}

/* Date picker specific styles follow */

a.date-picker {
	width: 16px;
	height: 16px;
	border: none;
	color: #fff;
	padding: 0;
	margin: 0;
	float: left;
	overflow: hidden;
	cursor: pointer;
	background: url({$icon}) no-repeat;
}
a.date-picker span {
	margin: 0 0 0 -2000px;
}
div.date-picker-holder, div.date-picker-holder * {
	margin: 0;
	padding: 0;
}
div.date-picker-holder {
	position: relative;
}
div.date-picker-holder input {
	float: left;
}
div.popup-calendar {
	display: none;
	position: absolute;
	z-index: 2;
	top: 0;
	left: -16px; /* value for IE */
	padding: 4px;
	border: 2px solid #000;
	background: #fff;
	color: #000;
	overflow:hidden;
	width: 163px;
}
html>body div.popup-calendar {
	left: 99px; /* value for decent browsers */
}
div.popup-calendar div.link-close {
	float: right;
}
div.popup-calendar div.link-prev {
	float: left;
}
div.popup-calendar h3 {
	font-size: 1.3em;
	margin: 2px 0 5px 3px;
}
div.popup-calendar div.link-next {
	float: right;
}
div.popup-calendar div a {
	padding: 1px 2px;
	color: #000;
}
div.popup-calendar div a:hover {
	background-color: #000;
	color: #fff;
}
div.popup-calendar table {
	margin: 0;
}
* html div.popup-calendar table {
	display: inline;
}
div.popup-calendar table th, div.popup-calendar table td {
	background: #eee;
	width: 21px;
	height: 17px;
	text-align: center;
}
div.popup-calendar table td.inactive {
	color: #aaa;
	padding: 1px 0 0;
}
div.popup-calendar table th.weekend, div.popup-calendar table td.weekend {
	background: #f6f6f6;
}
div.popup-calendar table td a {
	display: block;
	border: 1px solid #eee;
	width: 19px;
	height: 15px;
	text-decoration: none;
	color: #333;
}
div.popup-calendar table td.today a {
	border-color: #aaa;
}
div.popup-calendar table td a.selected, div.popup-calendar table td a:hover {
	background: #333;
	color: #fff;
}
EOF;

	return $css;
}

function zem_event_handler($event, $step) {

	$article_id = gps('ID') ? gps('ID') : @$GLOBALS['ID'];

	if (gps('save') or gps('publish')) {
		if (ps('zem_event_date')) {
			// insert or update
			$time = ps('zem_event_time') ? ps('zem_event_time') : NULL;
			$finish_date = ps('zem_finish_date') ? ps('zem_finish_date') : NULL;
			$finish_time = ps('zem_finish_time') ? ps('zem_finish_time') : NULL;
			$location = ps('zem_event_location');
			$location_url = ps('zem_event_location_url');
			$description = ps('zem_event_description');
			$email = ps('zem_event_email');
			$url = ps('zem_event_url');
			$name = ps('zem_event_name');
			$repeat_n = ps('zem_event_repeat_n');
			$repeat_period = ps('zem_event_repeat_period');
			$repeat_to = ps('zem_event_repeat_to');

			// simple array of category ids
			$categories = ps('zem_event_cats');

			// use article title as the default name
			if (!$name)
				$name = ps('Title');

			$id = zem_event_save($article_id, $name, ps('zem_event_date'), $time, $finish_date, $finish_time, $location, $location_url, $url, $email, $description, $categories, $repeat_n, $repeat_period, $repeat_to);
			if (!$id)
				trigger_error('Unable to save event');
		}
		else {
			// delete
			zem_event_delete($article_id);
		}
	}

	$row = safe_row('*', 'zem_event_calendar', "article_id='".doSlash($article_id)."' limit 1");

	if (zem_event_date_format() == 'MM/dd/yyyy')
		$format = '%m/%d/%Y';
	else
		$format = '%Y-%m-%d';

	$date = @$row['event_date'];
	if ($date)
		$date = strftime($format, strtotime($date));
	$time = @$row['event_time'];
	if ($time)
		$time = strftime('%H:%M', strtotime($time));
	$finish_date = @$row['finish_date'];
	if ($finish_date)
		$finish_date = strftime($format, strtotime($finish_date));
	$finish_time = @$row['finish_time'];
	if ($finish_time)
		$finish_time = strftime('%H:%M', strtotime($finish_time));
	$name = @$row['name'];
	$description = @$row['description'];
	$location = @$row['location'];
	$location_url = @$row['location_url'];
	$url = @$row['url'];
	$email = @$row['email'];

	$repeat_n = @$row['repeat_n'];
	$repeat_period = @$row['repeat_period'];
	$repeat_to = @$row['repeat_to'];
	if ($repeat_to)
		$repeat_to = strftime($format, strtotime($repeat_to));

	// build a dropdown of location choices
	$available_locations = zem_event_available_locations();

	// don't forget to grab categories from db
	// zem_event_cat_boxes() expects a simple array of category ids
	// if there are none, an empty string or array is fine
	$categories = array();
	if (@$row['id'])
		$categories = m2m_links('zem_event_category', $row['id']);

	$dates = array();
	if (@$row['id'])
		$dates = safe_column('event_date', 'zem_event_date', "event_id='".doSlash($row['id'])."'");

	$form =
		'<fieldset id="zem_event_fieldset">'.n.
		'<legend>'.zem_event_gTxt('event_label').'</legend>'.n.
		startTable('', '', 'zem_event', 5, '100%').
			tr(
				tda(
					n.graf('<label for="zem_event_name">'.zem_event_gTxt('name_label').'</label>'.br.
						fInput('text', 'zem_event_name', $name, 'edit', '', '', 40, '', 'zem_event_name')).

					n.graf('<label for="zem_event_description">'.zem_event_gTxt('description_label').'</label>'.br.
						text_area('zem_event_description', '75', '375', $description, 'zem_event_description'))
				, ' colspan="2"')
			).

			tr(
				td(
					n.graf('<label for="zem_event_date">'.zem_event_gTxt('date_label').'</label>'.br.
						fInput('text', 'zem_event_date', $date, 'zem_date_select', 'YYYY-MM-DD', '', 24, '', 'zem_event_date')).

					n.graf('<label for="zem_event_time">'.zem_event_gTxt('time_label').'</label>'.br.
						fInput('text', 'zem_event_time', $time, 'edit', 'HH:mm', '', 24, '', 'zem_event_time'), ' style="clear:both;"')
				).

				td(
					n.graf('<label for="zem_finish_date">'.zem_event_gTxt('finish_date_label').'</label>'.br.
						fInput('text', 'zem_finish_date', $finish_date, 'zem_date_select', 'YYYY-MM-DD', '', 24, '', 'zem_finish_date')).

					n.graf('<label for="zem_finish_time">'.zem_event_gTxt('finish_time_label').'</label>'.br.
						fInput('text', 'zem_finish_time', $finish_time, 'edit', 'HH:mm', '', 24, '', 'zem_finish_time'), ' style="clear:both;"')
				)
			).

			tr(
				td(
					n.graf('<label for="zem_event_location">'.zem_event_gTxt('location_label').'</label>'.br.
						selectInput('zem_event_location', $available_locations, $location, true, '', 'zem_event_location')).

					n.graf('<label for="zem_event_location_url">'.zem_event_gTxt('map_label').'</label>'.br.
					fInput('text', 'zem_event_location_url', $location_url, 'edit', '', '', 24, '', 'zem_event_location_url'))
				).

				td(
					n.graf('<label for="zem_event_url">'.zem_event_gTxt('url_label').'</label>'.br.
						fInput('text', 'zem_event_url', $url, 'edit', '', '', 24, '', 'zem_event_url')).

					n.graf('<label for="zem_event_email">'.zem_event_gTxt('email_label').'</label>'.br.
						fInput('text', 'zem_event_email', $email, 'edit', '', '', 24, '', 'zem_event_email'))
				)
			).

			tr(
				tda(
					n.'<fieldset id="zem_event_categories">'.
					'<legend>'.zem_event_gTxt('category_label').' <span class="small">[<a href="index.php?event=zem_event_cats">'.gTxt('edit').'</a>]</span></legend>'.
					zem_event_cat_boxes($categories).
					'</fieldset>'
				, ' colspan="2"')
			).

			tr(
				tda(
					n.'<fieldset id="zem_event_recurring">'.
					'<legend>'.zem_event_gTxt('recurring_label').'</legend>'.

					n.graf(

						zem_event_gTxt('repeat_n').br.
						fInput('text', 'zem_event_repeat_n', $repeat_n, 'edit', '', '', 4, '', 'zem_event_repeat_n').sp.
						selectInput('zem_event_repeat_period', array('day'=>'Day(s)','week'=>'Week(s)','month'=>'Month(s)','year'=>'Year(s)'),$repeat_period,true,'','zem_event_repeat_period').br.

						zem_event_gTxt('repeat_to').fInput('text', 'zem_event_repeat_to', $repeat_to, 'zem_date_select', 'YYYY-MM-DD', '', 24, '', 'zem_event_repeat_to')).
						($dates ? br.'Dates:'.br.join(br.n, $dates) : '').


					'</fieldset>'
				, ' colspan="2"')
			).

			endTable().n.
		'</fieldset>'.n;

	$html = $form;

	if (!is_file($path = txpath.'/js/jquery.js'))
		trigger_error(zem_event_gTxt('missing_file', array('{path}' => $path)));

	if (!is_file($path = txpath.'/js/datePicker.js'))
		trigger_error(zem_event_gTxt('missing_file', array('{path}' => $path)));

	echo script_js('document.write(\'<link rel="stylesheet" type="text/css" href="'.zem_file_url('datePicker.css').'" />\');');
	echo '<script type="text/javascript" src="'.hu.'textpattern/js/jquery.js"></script>';
	echo '<script type="text/javascript" src="'.hu.'textpattern/js/datePicker.js"></script>';

	echo dom_attach('article-main', $html) ;
	// add datePicker to all input tags with class zem_date_select

	if (zem_event_date_format() == 'MM/dd/yyyy') {
		$format = 'mdy';
		$sep = '/';
	}
	else {
		$format = 'ymd';
		$sep = '-';
	}

	echo script_js(
	'$.datePicker.setDateFormat(\''.$format.'\',\''.$sep.'\');
$(\'input.zem_date_select\').datePicker();'
	);
#	echo $html;
}

/*
	create array of available locations from form contents
	one location on each line
	name separated from title by an equal sign
*/

function zem_event_available_locations() {

	$form = fetch_form('zem_event_locations');

	// prepare form for use
	$form = str_replace(array("\r\n", "\r"), "\n", $form);

	$list = explode("\n", $form);

	$available_locations = array();

	foreach ($list as $key => $val) {
		$location = explode('=', $val);

		// only add to the list if both a name and title were supplied
		if ($location[0] and $location[1]) {
			list($name, $title) = doArray($location, 'trim');

			$available_locations[$name] = $title;
		}
	}

	return $available_locations;
}

/*
	create a list of category checkboxes
*/

function zem_event_cat_boxes($categories) {

	if (!is_array($categories)) {
		$categories = array();
	}

	$rs = getTree('root', 'event');

	if ($rs) {
		$out = array();

		$i = 0;

		foreach ($rs as $a) {
			extract($a);

			$i++;

			$out[] = n.graf(
				str_repeat(sp.sp, $level * 2).
				'<input type="checkbox" id="zem_event_cats_'.$id.'" class="checkbox" name="zem_event_cats[]" value="'.$id.'"'.
				(in_array($id, $categories) ? ' checked="checked"' : '').
				' />'.
				'<label for="zem_event_cats_'.$id.'">'.htmlspecialchars($title).'</label>'
			);
		}

		return join('', $out);
	} else {
		return gTxt('no_categories_exist');
	}
}

// relationship table functions

# fetch the links attached to a particular item (but not the
# linked rows themselves)
function m2m_links($rt, $v1) {

	# use array_values() to reindex from zero
	return array_values(safe_column('k2', $rt, "k1='".doSlash($v1)."'"));
}

function m2m_linked($rt, $v1, $lt, $k2='id') {
	return safe_rows('*', $rt.','.$lt, "{$rt}.k1='".doSlash($v1)."' and {$rt}.k2={$lt}.{$k2}");
}

# add a link
function m2m_add_link($rt, $v1, $v2) {

	# nb - there's a race condition here.
	# avoiding it probably isn't worth the extra complexity.
	if (!m2m_is_linked($rt, $v1, $v2))
		return safe_insert($rt, "k1='".doSlash($v1)."', k2='".doSlash($v2)."'");
}

# delete a specific link
function m2m_del_link($rt, $v1, $v2) {

	return safe_delete($rt, "k1='".doSlash($v1)."' AND k2='".doSlash($v2)."'");
}

# check whether or not two items are linked
function m2m_is_linked($rt, $v1, $v2) {

	$c = safe_count($rt, "k1='".doSlash($v1)."' AND k2='".doSlash($v2)."' limit 1");
	return $c;
}

function m2m_update_links($rt, $v1, $v2list) {

	# remove any links that aren't in the new list
	$links = m2m_links($rt, $v1);
	if ($links) {
		foreach ($links as $id) {
			if (!in_array($id, $v2list)) {
				if (!m2m_del_link($rt, $v1, $id))
					return false;
			}
		}
	}

	# add new links that weren't already there
	foreach ($v2list as $v2)
		if (!in_array($v2, $links)) {
			if (!m2m_add_link($rt, $v1, $v2))
				return false;
		}

	return true;
}

// callback event handlers

function zem_event_feed_entry($event, $step) {
	global $thisarticle;

	// append the event info to the excerpt, using the 'zem_event_display_feed' form
	$thisarticle['excerpt'] .= n.zem_article_event(array('form' => 'zem_event_display_feed'));
}

function zem_file_serve($data, $content_type='application/octet-stream') {
	header('Content-type: '.$content_type);
	echo $data;
	exit;
}

function zem_file_url($name) {
	return pagelinkurl(array('zf'=>$name));
}

function zem_file_img($name) {
	return '<img src="'.zem_file_url($name).'" />';
}

if (txpinterface === 'admin') {
	zem_event_install();
	register_callback('zem_event_handler', 'article');

	add_privs('zem_event_cats', '1,2,3,4,5');
	register_tab('content', 'zem_event_cats', zem_event_gTxt('event_categories'));
	register_callback('zem_event_cat_tab', 'zem_event_cats');
}

if (txpinterface === 'public') {
	register_callback('zem_event_feed_entry', 'atom_entry');
	register_callback('zem_event_feed_entry', 'rss_entry');

	if (gps('zf') === 'zem_event_cal.gif') {
		$cal_gif = <<<EOF
R0lGODlhEAAQAMQfAJe66f+ZWNji8PT3+2mW0Xei2O3y+P+yZf+iYlOFxLXN6qvG6V2MybzQ6/+6
c7HJ6f+IRf+pV+Lq9Iau4Y6z5X+o3V2Lxq7I6aPC7/9+PP///7rS88DT656/7P+SUP///yH5BAEA
AB8ALAAAAAAQABAAAAWL4CeOZCluWNcBLEVNUyWL2GDfuF2I3YD8iEDAA4FkBgSez8F0HA6RyDH5
AfiAQaHHgxRZNZpFePzQGBgiymDBvlweirhCYEkPLppyWaHhCxIiEwZwcoUXfyIVBnyMGg19GgJo
H4oNDXKXcnQiBQYcGp+fYGCbH50GEqkCq6ylBQSwDAwJtLW0JrgkIQA7
EOF;
		zem_file_serve(base64_decode($cal_gif));
	}

	if (gps('zf') === 'datePicker.css') {
		zem_file_serve(zem_event_datepicker_css(), 'text/css');
	}
}

function zem_event_gTxt($what, $atts = array()) {
	$lang = array(
		'categories_deleted'      => 'Event categories deleted: <strong>{list}</strong>.',
		'category_already_exists' => 'Event category <strong>{name}</strong> already exists.',
		'category_created'        => 'Event category <strong>{name}</strong> created.',
		'category_invalid'        => 'Event category <strong>{name}</strong> invalid.',
		'category_label'          => 'Categories',
		'category_name'           => 'Category name',
		'category_title'          => 'Category title',
		'category_updated'        => 'Event category <strong>{name}</strong> updated.',
		'date_label'              => 'Date',
		'description_label'       => 'Description',
		'email_label'             => 'Contact Email',
		'event_categories'        => 'Event Categories',
		'event_label'             => 'Event',
		'finish_date_label'       => 'Finish Date',
		'finish_time_label'       => 'Finish Time',
		'location_label'          => 'Location',
		'map_label'               => 'Map URL',
		'name_label'              => 'Name',
		'time_label'              => 'Time',
		'url_label'               => 'Event URL',
		'invalid_date'            => 'Invalid date <strong>{date}</strong>',
		'missing_file'            => 'Required file is missing: <strong>{path}</strong>',
		'recurring_label'         => 'Recurring event',
		'repeat_n'                => 'Repeat this event every',
		'repeat_to'               => 'Until',
	);

	return strtr($lang[$what], $atts);
}

/*
--- PLUGIN METADATA ---
Name: zem_event
Version: 0.30
Type: 1
Description: Event calendar
Author: Alex Shiels
Link: http://thresholdstate.com/
--- BEGIN PLUGIN HELP ---
	<h1>Textile-formatted help goes here</h1>
--- END PLUGIN HELP & METADATA ---
*/
?>