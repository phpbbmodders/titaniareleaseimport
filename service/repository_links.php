<?php
/**
 *
 * Titania Release Import extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\titaniareleaseimport\service;

use phpbb\db\driver\driver_interface;

/**
 * The repository each contribution's releases come from. Releases are only imported
 * from the repository its authors linked on the contribution's manage page.
 */
class repository_links
{
	public function __construct(
		private driver_interface $db,
		private string $table
	)
	{
	}

	/**
	 * The repository linked to a contribution.
	 *
	 * @param int $contrib_id
	 * @return array{provider:string,repository:string}|null
	 */
	public function get(int $contrib_id): array|null
	{
		$sql = 'SELECT provider, repository
			FROM ' . $this->table . '
			WHERE contrib_id = ' . (int) $contrib_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? ['provider' => $row['provider'], 'repository' => $row['repository']] : null;
	}

	/**
	 * Link a repository to a contribution, or remove the link with an empty repository.
	 *
	 * @param int    $contrib_id
	 * @param string $provider   Provider name, such as github
	 * @param string $repository Repository in the provider's short form
	 */
	public function set(int $contrib_id, string $provider, string $repository): void
	{
		$this->db->sql_transaction('begin');
		$this->db->sql_query('DELETE FROM ' . $this->table . ' WHERE contrib_id = ' . (int) $contrib_id);
		if ($repository !== '')
		{
			$this->db->sql_query('INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', [
				'contrib_id'	=> (int) $contrib_id,
				'provider'		=> $provider,
				'repository'	=> $repository,
			]));
		}
		$this->db->sql_transaction('commit');
	}
}
