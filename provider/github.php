<?php
/**
 *
 * Titania Release Import extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\titaniareleaseimport\provider;

use phpbb\config\config;
use phpbbmodders\titaniareleaseimport\exception\release_exception;
use phpbbmodders\titaniareleaseimport\service\http_client;

/**
 * Releases of public GitHub repositories, read through GitHub's REST API without a token.
 *
 * The API and web addresses can be changed with the titaniareleaseimport_github_api and
 * titaniareleaseimport_github_web settings, which have no form: they exist so tests can
 * point the extension at a local stand-in for GitHub.
 */
class github implements provider_interface
{
	/** An owner: letters, digits and single hyphens, at most 39 characters */
	private const OWNER = '[A-Za-z0-9](?:[A-Za-z0-9]|-(?=[A-Za-z0-9])){0,38}';

	/** A repository name: letters, digits, dots, hyphens and underscores */
	private const NAME = '[A-Za-z0-9._-]{1,100}';

	/** Where release files are served from after github.com redirects a download */
	private const FILE_HOSTS = [
		'https://objects.githubusercontent.com/',
		'https://release-assets.githubusercontent.com/',
	];

	private string $api;

	private string $web;

	public function __construct(
		private http_client $http,
		config $config
	)
	{
		$this->api = rtrim((string) ($config['titaniareleaseimport_github_api'] ?: 'https://api.github.com'), '/') . '/';
		$this->web = rtrim((string) ($config['titaniareleaseimport_github_web'] ?: 'https://github.com'), '/') . '/';
	}

	public function get_name(): string
	{
		return 'github';
	}

	public function get_label(): string
	{
		return 'GitHub';
	}

	public function parse_repository(string $input): string|null
	{
		$input = trim($input);
		$web = preg_quote(preg_replace('#^https?://#', '', $this->web), '#');
		$pattern = '#^(?:(?:https?://)?' . $web . ')?(' . self::OWNER . ')/(' . self::NAME . ')/?$#';
		if (!preg_match($pattern, preg_replace('#\.git$#', '', $input), $match) || in_array($match[2], ['.', '..'], true))
		{
			return null;
		}

		return $match[1] . '/' . $match[2];
	}

	public function get_repository_url(string $repository): string
	{
		return $this->web . $repository;
	}

	public function parse_release_url(string $url): array|null
	{
		$web = preg_quote(preg_replace('#^https?://#', '', $this->web), '#');
		$pattern = '#^(?:https?://)?' . $web . '(' . self::OWNER . ')/(' . self::NAME . ')/releases/tag/([^?\#]+)$#';
		if (!preg_match($pattern, trim($url), $match))
		{
			return null;
		}

		return ['repository' => $match[1] . '/' . $match[2], 'tag' => rawurldecode($match[3])];
	}

	public function get_release(string $repository, string $tag): array
	{
		[$owner, $name] = explode('/', $repository, 2);
		$url = $this->api . 'repos/' . rawurlencode($owner) . '/' . rawurlencode($name) . '/releases/tags/' . rawurlencode($tag);
		$response = $this->http->get_json($url, $this->get_allowed_prefixes(), [
			'Accept'				=> 'application/vnd.github+json',
			'X-GitHub-Api-Version'	=> '2022-11-28',
		]);

		if ($response['status'] === 404)
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_RELEASE_NOT_FOUND', [$tag, $repository]);
		}
		if (($response['status'] === 403 || $response['status'] === 429) && ($response['headers']['x-ratelimit-remaining'] ?? '') === '0')
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_RATE_LIMIT', [$this->get_label()]);
		}
		if ($response['status'] !== 200 || !is_array($response['data']) || !isset($response['data']['assets']) || !is_array($response['data']['assets']))
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_SERVICE', [$this->get_label()]);
		}

		$assets = [];
		foreach ($response['data']['assets'] as $asset)
		{
			if (is_array($asset) && isset($asset['name'], $asset['browser_download_url']) && is_string($asset['name']) && is_string($asset['browser_download_url']))
			{
				$assets[] = [
					'name'			=> $asset['name'],
					'size'			=> (int) ($asset['size'] ?? 0),
					'url'			=> $asset['browser_download_url'],
					'content_type'	=> is_string($asset['content_type'] ?? null) ? $asset['content_type'] : 'application/octet-stream',
				];
			}
		}

		return [
			'tag'		=> is_string($response['data']['tag_name'] ?? null) ? $response['data']['tag_name'] : $tag,
			'name'		=> is_string($response['data']['name'] ?? null) ? $response['data']['name'] : '',
			'assets'	=> $assets,
		];
	}

	public function get_allowed_prefixes(): array
	{
		$prefixes = [$this->api, $this->web];

		// With the default addresses, downloads are redirected to GitHub's file hosts
		if ($this->api === 'https://api.github.com/' && $this->web === 'https://github.com/')
		{
			$prefixes = array_merge($prefixes, self::FILE_HOSTS);
		}

		return $prefixes;
	}
}
