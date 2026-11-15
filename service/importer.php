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

use phpbb\config\config;
use phpbbmodders\titaniareleaseimport\exception\release_exception;

/**
 * Fetches a release's ZIP file from the contribution's linked repository and hands it
 * to Titania's uploader, which checks it like any upload.
 */
class importer
{
	/** Default largest file downloaded; Titania's own size limit still applies afterwards */
	public const DEFAULT_MAX_BYTES = 52428800;

	public function __construct(
		private provider_loader $providers,
		private repository_links $links,
		private http_client $http,
		private config $config,
		private string $temp_dir
	)
	{
	}

	/**
	 * Import a release as the revision's file.
	 *
	 * @param int    $contrib_id The contribution the revision is for
	 * @param string $release    A release tag, or the address of the release's page
	 * @param string $asset      The file to use when the release has more than one ZIP file
	 * @param object $uploader   Titania's revision uploader (\phpbb\titania\attachment\uploader)
	 * @return array{tag:string,name:string,asset:string} What was imported
	 * @throws release_exception When the release cannot be imported
	 */
	public function import(int $contrib_id, string $release, string $asset, object $uploader): array
	{
		$link = $this->links->get($contrib_id);
		$provider = $link ? $this->providers->get($link['provider']) : null;
		if (!$link || !$provider)
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_NO_REPOSITORY');
		}

		// A release page address must belong to the linked repository; anything else is a tag
		$tag = trim($release);
		$parsed = $provider->parse_release_url($tag);
		if ($parsed !== null)
		{
			if (strcasecmp($parsed['repository'], $link['repository']) !== 0)
			{
				throw new release_exception('TITANIARELEASEIMPORT_ERR_OTHER_REPOSITORY', [$parsed['repository'], $link['repository']]);
			}
			$tag = $parsed['tag'];
		}
		else if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $tag))
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_RELEASE_ADDRESS', [$provider->get_label()]);
		}
		if ($tag === '' || strlen($tag) > 255)
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_NO_RELEASE');
		}

		$info = $provider->get_release($link['repository'], $tag);
		$file = $this->choose_asset($info['assets'], trim($asset), $info['tag']);

		$max_bytes = (int) $this->config['titaniareleaseimport_max_bytes'] ?: self::DEFAULT_MAX_BYTES;
		if ($file['size'] > $max_bytes)
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_TOO_LARGE', [get_formatted_filesize($max_bytes)]);
		}

		$target = tempnam($this->temp_dir, 'titaniareleaseimport_');
		if ($target === false)
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_DOWNLOAD');
		}

		try
		{
			$this->http->download($file['url'], $provider->get_allowed_prefixes(), $max_bytes, $target);
			if (!$uploader->add_local_file($target, $file['name'], 'application/zip'))
			{
				// Titania's uploader keeps its own errors, which are shown with the form
				return [];
			}
		}
		finally
		{
			// The uploader moves the file into Titania's files folder; anything left is removed
			if (is_file($target))
			{
				@unlink($target);
			}
		}

		return ['tag' => $info['tag'], 'name' => $info['name'], 'asset' => $file['name']];
	}

	/**
	 * Pick the release's ZIP file.
	 *
	 * @param array  $assets The release's files
	 * @param string $wanted The file the author named, or empty
	 * @param string $tag    The release's tag, for messages
	 * @return array{name:string,size:int,url:string,content_type:string}
	 * @throws release_exception When there is no ZIP file, or several and none was named
	 */
	public function choose_asset(array $assets, string $wanted, string $tag): array
	{
		$zips = array_values(array_filter($assets, static fn (array $file): bool => strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'zip'));

		if ($wanted !== '')
		{
			foreach ($zips as $file)
			{
				if ($file['name'] === $wanted)
				{
					return $file;
				}
			}
			throw new release_exception('TITANIARELEASEIMPORT_ERR_ASSET_NOT_FOUND', [$wanted, $tag]);
		}

		if (!$zips)
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_NO_ZIP', [$tag]);
		}
		if (count($zips) > 1)
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_SEVERAL_ZIPS', [$tag, implode(', ', array_column($zips, 'name'))]);
		}

		return $zips[0];
	}
}
