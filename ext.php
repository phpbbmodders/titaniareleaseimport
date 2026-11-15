<?php
/**
 *
 * Titania Release Import extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\titaniareleaseimport;

class ext extends \phpbb\extension\base
{
	/**
	 * Check whether the extension can be enabled: phpBB 4.0, PHP 8.2 or later, and a Titania
	 * that has the events and the local file upload this extension uses.
	 *
	 * @return bool|string True, or the reason it cannot be enabled
	 */
	public function is_enableable()
	{
		$language = $this->container->get('language');
		$language->add_lang('install_titaniareleaseimport', 'phpbbmodders/titaniareleaseimport');

		if (!$this->check_phpbb_version() || !$this->check_php_version())
		{
			return $language->lang('TITANIARELEASEIMPORT_NOT_ENABLEABLE');
		}

		if (!$this->container->get('ext.manager')->is_enabled('phpbb/titania') || !$this->titania_has_events())
		{
			return $language->lang('TITANIARELEASEIMPORT_NEEDS_TITANIA');
		}

		return true;
	}

	/**
	 * Requires phpBB 4.0.
	 *
	 * @return bool
	 */
	public function check_phpbb_version()
	{
		return phpbb_version_compare(PHPBB_VERSION, '4.0.0-a1', '>=')
			&& phpbb_version_compare(PHPBB_VERSION, '4.1.0-dev', '<');
	}

	/**
	 * Requires PHP 8.2.0 or later, the same as phpBB 4.0.
	 *
	 * @return bool
	 */
	public function check_php_version()
	{
		return PHP_VERSION_ID >= 80200;
	}

	/**
	 * Titania gained its extension events, and the uploader's add_local_file(), together.
	 *
	 * @return bool
	 */
	protected function titania_has_events()
	{
		return method_exists('\phpbb\titania\attachment\uploader', 'add_local_file');
	}
}
