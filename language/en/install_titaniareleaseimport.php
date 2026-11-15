<?php
/**
 *
 * Titania Release Import extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'TITANIARELEASEIMPORT_NOT_ENABLEABLE'	=> 'Titania Release Import could not be enabled. It needs phpBB 4.0 and PHP 8.2 or later.',
	'TITANIARELEASEIMPORT_NEEDS_TITANIA'	=> 'Titania Release Import could not be enabled. It needs Titania (the Customisation Database) enabled first, in a version with extension events.',
]);
