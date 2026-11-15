<?php
/**
 *
 * Titania Release Import extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\titaniareleaseimport\migrations\v10x;

/**
 * Adds the table of repositories linked to contributions, and the download limit
 */
class install extends \phpbb\db\migration\migration
{
	/**
	 * {@inheritdoc}
	 */
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'titaniareleaseimport_repos');
	}

	/**
	 * {@inheritdoc}
	 */
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v400\v400a1'];
	}

	/**
	 * One row per contribution with a linked repository
	 *
	 * {@inheritdoc}
	 */
	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'titaniareleaseimport_repos' => [
					'COLUMNS' => [
						'contrib_id'	=> ['UINT', 0],
						'provider'		=> ['VCHAR:32', ''],
						'repository'	=> ['VCHAR:255', ''],
					],
					'PRIMARY_KEY'	=> 'contrib_id',
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'titaniareleaseimport_repos',
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function update_data()
	{
		return [
			// Largest release file downloaded, in bytes; Titania's own upload limit still applies
			['config.add', ['titaniareleaseimport_max_bytes', 52428800]],
		];
	}
}
