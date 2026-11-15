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
	// Contribution manage page
	'TITANIARELEASEIMPORT_REPOSITORY'			=> 'Source repository',
	'TITANIARELEASEIMPORT_REPOSITORY_EXPLAIN'	=> 'The repository this contribution’s releases are published in, such as https://github.com/owner/name. New revisions can then be imported from its releases. Leave empty to remove the link.',
	'TITANIARELEASEIMPORT_SERVICE'				=> 'Service',

	// New Revision page
	'TITANIARELEASEIMPORT_IMPORT'				=> 'Import from a release',
	'TITANIARELEASEIMPORT_IMPORT_EXPLAIN'		=> 'Instead of uploading a file, enter a release of %1$s on %2$s: its tag, such as v1.2.0, or the address of its release page. Its ZIP file is used as this revision’s file.',
	'TITANIARELEASEIMPORT_RELEASE'				=> 'Release',
	'TITANIARELEASEIMPORT_ASSET'				=> 'File name',
	'TITANIARELEASEIMPORT_ASSET_EXPLAIN'		=> 'Only needed when the release has more than one ZIP file.',
	'TITANIARELEASEIMPORT_NOT_LINKED'			=> 'To import new revisions from a GitHub release, <a href="%s">link the contribution’s source repository</a>.',

	// Errors
	'TITANIARELEASEIMPORT_ERR_REPOSITORY'		=> 'The source repository is not a %s repository address.',
	'TITANIARELEASEIMPORT_ERR_NO_REPOSITORY'	=> 'This contribution has no source repository linked, so releases cannot be imported.',
	'TITANIARELEASEIMPORT_ERR_NO_RELEASE'		=> 'Enter the release’s tag or the address of its release page.',
	'TITANIARELEASEIMPORT_ERR_RELEASE_ADDRESS'	=> 'That is not a %s release page address. Enter the release’s tag or the address of its release page.',
	'TITANIARELEASEIMPORT_ERR_OTHER_REPOSITORY'	=> 'That release belongs to %1$s, not to this contribution’s repository, %2$s.',
	'TITANIARELEASEIMPORT_ERR_RELEASE_NOT_FOUND'	=> 'There is no published release %1$s in %2$s.',
	'TITANIARELEASEIMPORT_ERR_RATE_LIMIT'		=> '%s is refusing more requests from this board for now. Try again later, or upload the file instead.',
	'TITANIARELEASEIMPORT_ERR_SERVICE'			=> '%s did not return the release. Try again later, or upload the file instead.',
	'TITANIARELEASEIMPORT_ERR_CONNECT'			=> 'The release could not be fetched. Try again later, or upload the file instead.',
	'TITANIARELEASEIMPORT_ERR_ADDRESS'			=> 'The release file is not served from an address this board may download from.',
	'TITANIARELEASEIMPORT_ERR_DOWNLOAD'			=> 'The release file could not be downloaded.',
	'TITANIARELEASEIMPORT_ERR_TOO_LARGE'		=> 'The release file is larger than %s.',
	'TITANIARELEASEIMPORT_ERR_NO_ZIP'			=> 'Release %s has no ZIP file.',
	'TITANIARELEASEIMPORT_ERR_SEVERAL_ZIPS'		=> 'Release %1$s has more than one ZIP file. Enter the one to use under File name: %2$s.',
	'TITANIARELEASEIMPORT_ERR_ASSET_NOT_FOUND'	=> 'Release %2$s has no ZIP file named %1$s.',
]);
