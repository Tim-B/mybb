<?php
/**
 * MyBB 1.4
 * Copyright © 2007 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/about/license
 *
 * $Id$
 */

// Load main MyBB core file which begins all of the magic
require_once "./inc/init.php";

$shutdown_queries = array();

// Read the usergroups cache as well as the moderators cache
$groupscache = $cache->read("usergroups");

// If the groups cache doesn't exist, update it and re-read it
if(!is_array($groupscache))
{
	$cache->update_usergroups();
	$groupscache = $cache->read("usergroups");
}

// Send page headers
send_page_headers();

// Do not use session system for defined pages
if((@isset($mybb->input['action']) && @isset($nosession[$mybb->input['action']])) || (@isset($mybb->input['thumbnail']) && my_strpos($_SERVER['PHP_SELF'], 'attachment.php')))
{
	define("NO_ONLINE", 1);
}

// Create session for this user
require_once MYBB_ROOT."inc/class_session.php";
$session = new session;
$session->init();
$mybb->session = &$session;

// Set our POST validation code here
$mybb->post_code = generate_post_check();

// Set and load the language
if($mybb->input['language'] && $lang->language_exists($mybb->input['language']))
{
	$mybb->settings['bblanguage'] = $mybb->input['language'];
	// If user is logged in, update their language selection with the new one
	if($mybb->user['uid'])
	{
		$updated_lang = array("language" => $mybb->settings['bblanguage']);
		$db->update_query("users", $updated_lang, "uid='{$mybb->user['uid']}'");
	}
	// Guest = cookie
	else
	{
		my_setcookie("mybblang", $mybb->settings['bblang']);
	}
}
// Cookied language!
else if($_COOKIE['mybblang'] && $lang->language_exists($_COOKIE['mybblang']))
{
	$mybb->settings['bblanguage'] = $mybb->input['language'];
}
else if(!isset($mybb->settings['bblanguage']))
{
	$mybb->settings['bblanguage'] = "english";
}

// Load language
$lang->set_language($mybb->settings['bblanguage']);
$lang->load("global");
$lang->load("messages");

// Run global_start plugin hook now that the basics are set up
$plugins->run_hooks("global_start");

if(function_exists('mb_internal_encoding') && !empty($lang->settings['charset']))
{
	@mb_internal_encoding($lang->settings['charset']);
}

// Select the board theme to use.
$loadstyle = '';
$load_from_forum = 0;
$style = array();

// This user has a custom theme set in their profile
if(isset($mybb->user['style']) && intval($mybb->user['style']) != 0)
{
	$loadstyle = "tid='".$mybb->user['style']."'";
}

$valid = array(
	"showthread.php", 
	"forumdisplay.php",
	"newthread.php",
	"newreply.php",
	"ratethread.php",
	"editpost.php",
	"polls.php",
	"sendthread.php",
	"printthread.php",
	"moderation.php"	
);

if(in_array(strtolower(basename($_SERVER['PHP_SELF'])), $valid))
{
	// If we're accessing a post, fetch the forum theme for it and if we're overriding it
	if(isset($mybb->input['pid']))
	{
		$query = $db->query("
			SELECT f.style, f.overridestyle, p.*
			FROM ".TABLE_PREFIX."forums f
			LEFT JOIN ".TABLE_PREFIX."posts p ON(f.fid=p.fid) 
			WHERE p.pid='".intval($mybb->input['pid'])."'
			LIMIT 1
		");
		$style = $db->fetch_array($query);
		$load_from_forum = 1;
	}
	
	// We have a thread id and a forum id, we can easily fetch the theme for this forum
	else if(isset($mybb->input['tid']))
	{
		$query = $db->query("
			SELECT f.style, f.overridestyle, t.*
			FROM ".TABLE_PREFIX."forums f
			LEFT JOIN ".TABLE_PREFIX."threads t ON (f.fid=t.fid)
			WHERE t.tid='".intval($mybb->input['tid'])."'
			LIMIT 1
		");
		$style = $db->fetch_array($query);
		$load_from_forum = 1;
	}
	
	// We have a forum id - simply load the theme from it
	else if(isset($mybb->input['fid']))
	{
		//$query = $db->simple_select("forums", "style, overridestyle", "fid='".intval($mybb->input['fid'])."'", array('limit' => 1));
		//$style = $db->fetch_array($query);
		cache_forums();
		$style = $forum_cache[intval($mybb->input['fid'])];
		$load_from_forum = 1;
	}
}

// From all of the above, a theme was found
if(isset($style['style']) && $style['style'] > 0)
{
	// This theme is forced upon the user, overriding their selection
	if($style['overridestyle'] == 1 || !isset($mybb->user['style']))
	{
		$loadstyle = "tid='".intval($style['style'])."'";
	}
}

// After all of that no theme? Load the board default
if(empty($loadstyle))
{
	$loadstyle = "def='1'";
}

// Fetch the theme to load from the database
$query = $db->simple_select("themes", "name, tid, properties, stylesheets", $loadstyle, array('limit' => 1));
$theme = $db->fetch_array($query);

// No theme was found - we attempt to load the master or any other theme
if(!$theme['tid'])
{
	// Missing theme was from a forum, run a query to set any forums using the theme to the default
	if($load_from_forum == 1)
	{
		$db->update_query("forums", array("style" => 0), "style='{$style['style']}'");
	}
	// Missing theme was from a user, run a query to set any users using the theme to the default
	else if($load_from_user == 1)
	{
		$db->update_query("users", array("style" => 0), "style='{$style['style']}'");
	}
	// Attempt to load the master or any other theme if the master is not available
	$query = $db->simple_select("themes", "name, tid, properties, stylesheets", "", array("order_by" => "tid", "limit" => 1));
	$theme = $db->fetch_array($query);
}
$theme = @array_merge($theme, unserialize($theme['properties']));

// Fetch all necessary stylesheets
$theme['stylesheets'] = unserialize($theme['stylesheets']);
$stylesheet_scripts = array("global", basename($_SERVER['PHP_SELF']));
foreach($stylesheet_scripts as $stylesheet_script)
{
	$stylesheet_actions = array("global");
	if($mybb->input['action'])
	{
		$stylesheet_actions[] = $mybb->input['action'];
	}
	// Load stylesheets for global actions and the current action
	foreach($stylesheet_actions as $stylesheet_action)
	{
		if(!$stylesheet_action) continue;
		if($theme['stylesheets'][$stylesheet_script][$stylesheet_action])
		{
			// Actually add the stylesheets to the list
			foreach($theme['stylesheets'][$stylesheet_script][$stylesheet_action] as $page_stylesheet)
			{
				if($already_loaded[$page_stylesheet]) continue;
				$stylesheets .= "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$mybb->settings['bburl']}/{$page_stylesheet}\" />\n";
				$already_loaded[$page_stylesheet] = 1;
			}
		}
	}
}

if(!@is_dir($theme['imgdir']))
{
	$theme['imgdir'] = "images";
} 

// If a language directory for the current language exists within the theme - we use it
if(!empty($mybb->user['language']) && is_dir($theme['imgdir'].'/'.$mybb->user['language']))
{
	$theme['imglangdir'] = $theme['imgdir'].'/'.$mybb->user['language'];
}
else
{
	// Check if a custom language directory exists for this theme
	if(is_dir($theme['imgdir'].'/'.$mybb->settings['bblanguage']))
	{
		$theme['imglangdir'] = $theme['imgdir'].'/'.$mybb->settings['bblanguage'];
	}
	// Otherwise, the image language directory is the same as the language directory for the theme
	else
	{
		$theme['imglangdir'] = $theme['imgdir'];
	}
}

// Theme logo - is it a relative URL to the forum root? Append bburl
if(!preg_match("#^(\/|\.\.|\.|([a-z0-9]+)://)#i", $theme['logo']))
{
	$theme['logo'] = $mybb->settings['bburl']."/".$theme['logo'];
}

// Load Main Templates and Cached Templates
if(isset($templatelist))
{
	$templatelist .= ',';
}
$templatelist .= "css,headerinclude,header,footer,gobutton,htmldoctype,header_welcomeblock_member,header_welcomeblock_guest,header_welcomeblock_member_admin,global_pm_alert,global_unreadreports";
$templatelist .= ",nav,nav_sep,nav_bit,nav_sep_active,nav_bit_active,footer_languageselect,header_welcomeblock_member_moderator,redirect,error";
$templates->cache($db->escape_string($templatelist));

// Set the current date and time now
$datenow = my_date($mybb->settings['dateformat'], TIME_NOW, '', false);
$timenow = my_date($mybb->settings['timeformat'], TIME_NOW);
$lang->welcome_current_time = $lang->sprintf($lang->welcome_current_time, $datenow.', '.$timenow);

// Format the last visit date of this user appropriately
if(isset($mybb->user['lastvisit']))
{
	$lastvisit = my_date($mybb->settings['dateformat'], $mybb->user['lastvisit']) . ', ' . my_date($mybb->settings['timeformat'], $mybb->user['lastvisit']);
}

// Otherwise, they've never visited before
else
{
	$lastvisit = $lang->lastvisit_never;
}

// If the board is closed and we have an Administrator, show board closed warning
$bbclosedwarning = '';
if($mybb->settings['boardclosed'] == 1 && $mybb->usergroup['cancp'] == 1)
{
	eval("\$bbclosedwarning = \"".$templates->get("global_boardclosed_warning")."\";");
}

// Prepare the main templates for use
unset($admincplink);

// Load appropriate welcome block for the current logged in user
if($mybb->user['uid'] != 0)
{
	// User can access the admin cp and we're not hiding admin cp links, fetch it
	if($mybb->usergroup['cancp'] == 1 && $mybb->config['hide_admin_links'] != 1)
	{
		eval("\$admincplink = \"".$templates->get("header_welcomeblock_member_admin")."\";");
	}
	
	if(is_moderator("", "", $mybb->user['uid']))
	{
		eval("\$modcplink = \"".$templates->get("header_welcomeblock_member_moderator")."\";");
	}
	
	// Format the welcome back message
	$lang->welcome_back = $lang->sprintf($lang->welcome_back, $mybb->user['username'], $lastvisit);

	// Tell the user their PM usage
	$lang->welcome_pms_usage = $lang->sprintf($lang->welcome_pms_usage, my_number_format($mybb->user['pms_unread']), my_number_format($mybb->user['pms_total']));
	eval("\$welcomeblock = \"".$templates->get("header_welcomeblock_member")."\";");
}
// Otherwise, we have a guest
else
{
	eval("\$welcomeblock = \"".$templates->get("header_welcomeblock_guest")."\";");
}

$unreadreports = '';
// This user is a moderator, super moderator or administrator
if($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['issupermod'] == 1 || $mybb->user['usergroup'] == 6)
{
	// Read the reported posts cache
	$reported = $cache->read("reportedposts");

	// 0 or more reported posts currently exist
	if($reported['unread'] > 0)
	{
		if($reported['unread'] == 1)
		{
			$lang->unread_reports = $lang->unread_report;
		}
		else
		{
			$lang->unread_reports = $lang->sprintf($lang->unread_reports, $reported['unread']);
		}
		eval("\$unreadreports = \"".$templates->get("global_unreadreports")."\";");
	}
}

// Got a character set?
if($lang->settings['charset'])
{
	$charset = $lang->settings['charset'];
}
// If not, revert to UTF-8
else
{
	$charset = "UTF-8";
}

// Is this user apart of a banned group?
$bannedwarning = '';
if($mybb->usergroup['isbannedgroup'] == 1)
{
	// Fetch details on their ban
	$query = $db->simple_select("banned", "*", "uid='{$mybb->user['uid']}'", array('limit' => 1));
	$ban = $db->fetch_array($query);
	if($ban['uid'])
	{
		// Format their ban lift date and reason appropriately
		if($ban['lifted'] > 0)
		{
			$banlift = my_date($mybb->settings['dateformat'], $ban['lifted']) . ", " . my_date($mybb->settings['timeformat'], $ban['lifted']);
		}
		else 
		{
			$banlift = $lang->banned_lifted_never;
		}
		$reason = htmlspecialchars_uni($ban['reason']);
	}
	if(empty($reason))
	{
		$reason = $lang->unknown;
	}
	if(empty($banlift))
	{
		$banlift = $lang->unknown;
	}
	if($ban['uid'])
	{
		// Display a nice warning to the user
	}	eval("\$bannedwarning = \"".$templates->get("global_bannedwarning")."\";");
}

$lang->ajax_loading = str_replace("'", "\\'", $lang->ajax_loading);

// Check if this user has a new private message.
if($mybb->user['pmnotice'] == 2 && $mybb->user['pms_unread'] > 0 && $mybb->settings['enablepms'] != 0 && $mybb->usergroup['canusepms'] != 0 && $mybb->usergroup['canview'] != 0 && my_strpos(get_current_location(), 'private.php?action=read') === false)
{
	$query = $db->query("
		SELECT pm.subject, pm.pmid, fu.username AS fromusername, fu.uid AS fromuid
		FROM ".TABLE_PREFIX."privatemessages pm
		LEFT JOIN ".TABLE_PREFIX."users fu ON (fu.uid=pm.fromid)
		WHERE pm.folder='1' AND pm.uid='{$mybb->user['uid']}' AND pm.status='0'
		ORDER BY pm.dateline DESC
		LIMIT 1
	");
	$pm = $db->fetch_array($query);
	if($mybb->user['pms_unread'] == 1)
	{
		$privatemessage_text = $lang->sprintf($lang->newpm_notice_one, get_profile_link($pm['fromuid']), $pm['fromusername'], $pm['pmid'], $pm['subject']);
	}
	else
	{
		$privatemessage_text = $lang->sprintf($lang->newpm_notice_multiple, $mybb->user['pms_unread'], get_profile_link($pm['fromuid']), $pm['fromusername'], $pm['pmid'], $pm['subject']);
	}
	eval("\$pm_notice = \"".$templates->get("global_pm_alert")."\";");
}

// Set up some of the default templates
eval("\$headerinclude = \"".$templates->get("headerinclude")."\";");
eval("\$gobutton = \"".$templates->get("gobutton")."\";");
eval("\$htmldoctype = \"".$templates->get("htmldoctype", 1, 0)."\";");
eval("\$header = \"".$templates->get("header")."\";");

$copy_year = my_date("Y", TIME_NOW);

// Are we showing version numbers in the footer?
if($mybb->settings['showvernum'] == 1)
{
	$mybbversion = ' '.$mybb->version;
}
else
{
	$mybbversion = '';
}

// Check to see if we have any tasks to run
if($mybb->settings['taskscron'] != 1)
{
	$task_cache = $cache->read("tasks");
	if(!$task_cache['nextrun'])
	{
		$task_cache['nextrun'] = TIME_NOW;
	}
	if($task_cache['nextrun'] <= TIME_NOW)
	{
		$task_image = "<img src=\"{$mybb->settings['bburl']}/task.php\" border=\"0\" width=\"1\" height=\"1\" alt=\"\" />";
	}
	else
	{
		$task_image = '';
	}
}

// Are we showing the quick language selection box?
$lang_select = '';
if($mybb->settings['showlanguageselect'] != 0)
{
	$languages = $lang->get_languages();
	foreach($languages as $key => $language)
	{
		// Current language matches
		if($lang->language == $key)
		{
			$lang_options .= "<option value=\"{$key}\" selected=\"selected\">&nbsp;&nbsp;&nbsp;{$language}</option>\n";
		}
		else
		{
			$lang_options .= "<option value=\"{$key}\">&nbsp;&nbsp;&nbsp;{$language}</option>\n";
		}
	}
	
	$lang_redirect_url = get_current_location(true, 'language');
	
	eval("\$lang_select = \"".$templates->get("footer_languageselect")."\";");
}

// DST Auto detection enabled?
if($mybb->user['uid'] > 0 && $mybb->user['dstcorrection'] == 2)
{
	$auto_dst_detection = "<script type=\"text/javascript\">if(MyBB) { Event.observe(window, 'load', function() { MyBB.detectDSTChange('".($mybb->user['timezone']+$mybb->user['dst'])."'); }); }</script>\n";
}

eval("\$footer = \"".$templates->get("footer")."\";");

// Add our main parts to the navigation
$navbits = array();
$navbits[0]['name'] = $mybb->settings['bbname_orig'];
$navbits[0]['url'] = $mybb->settings['bburl']."/index.php";

// Set the link to the archive.
$archive_url = $mybb->settings['bburl']."/archive/index.php";

// Check banned ip addresses
if(is_banned_ip($session->ipaddress, true))
{
	$db->delete_query("sessions", "ip='".$db->escape_string($session->ipaddress)."' OR uid='{$mybb->user['uid']}'");
	error($lang->error_banned);
}

// If the board is closed, the user is not an administrator and they're not trying to login, show the board closed message
if($mybb->settings['boardclosed'] == 1 && $mybb->usergroup['cancp'] != 1 && !(basename($_SERVER['PHP_SELF']) == "member.php" && ($mybb->input['action'] == "login" || $mybb->input['action'] == "do_login" || $mybb->input['action'] == "logout")))
{
	// Show error
	$lang->error_boardclosed .= "<blockquote>{$mybb->settings['boardclosed_reason']}</blockquote>";
	error($lang->error_boardclosed);
	exit;
}

// Load Limiting
if(($load = get_server_load()) && $load != $lang->unknown)
{
	// User is not an administrator and the load limit is higher than the limit, show an error
	if($mybb->usergroup['cancp'] != 1 && $load > $mybb->settings['load'] && $mybb->settings['load'] > 0)
	{
		error($lang->error_loadlimit);
	}
}

// If there is a valid referrer in the URL, cookie it
if(!$mybb->user['uid'] && $mybb->settings['usereferrals'] == 1 && (isset($mybb->input['referrer']) || isset($mybb->input['referrername'])))
{
	if(isset($mybb->input['referrername']))
	{
		$condition = "username='".$db->escape_string($mybb->input['referrername'])."'";
	}
	else
	{
		$condition = "uid='".intval($mybb->input['referrer'])."'";
	}
	$query = $db->simple_select("users", "uid", $condition, array('limit' => 1));
	$referrer = $db->fetch_array($query);
	if($referrer['uid'])
	{
		my_setcookie("mybb[referrer]", $referrer['uid']);
	}
}

// Check pages allowable even when not allowed to view board
$allowable_actions = array(
	"member.php" => array(
		"register",
		"do_register",
		"login",
		"do_login",
		"logout",
		"lostpw",
		"do_lostpw",
		"activate",
		"resendactivation",
		"do_resendactivation",
		"resetpassword"
	),
	"usercp2.php" => array(
		"removesubscription",
		"removesubscriptions"
	),
);
if($mybb->usergroup['canview'] != 1 && !(my_strtolower(basename($_SERVER['PHP_SELF'])) == "member.php" && in_array($mybb->input['action'], $allowable_actions['member.php'])) && !(my_strtolower(basename($_SERVER['PHP_SELF'])) == "usercp2.php" && in_array($mybb->input['action'], $allowable_actions['usercp2.php'])) && my_strtolower(basename($_SERVER['PHP_SELF'])) != "captcha.php")
{
	error_no_permission();
}

// work out which items the user has collapsed
$colcookie = $_COOKIE['collapsed'];

// set up collapsable items (to automatically show them us expanded)
if($colcookie)
{
	$col = explode("|", $colcookie);
	if(!is_array($col))
	{
		$col[0] = $colcookie; // only one item
	}
	unset($collapsed);
	foreach($col as $key => $val)
	{
		$ex = $val."_e";
		$co = $val."_c";
		$collapsed[$co] = "display: show;";
		$collapsed[$ex] = "display: none;";
		$collapsedimg[$val] = "_collapsed";
	}
}

// Run hooks for end of global.php
$plugins->run_hooks("global_end");

$globaltime = $maintimer->getTime();
?>