<?php
/**
 *
 * Titania Release Import extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\titaniareleaseimport\tests;

use phpbbmodders\titaniareleaseimport\provider\github;
use phpbbmodders\titaniareleaseimport\service\http_client;

class github_provider_test extends \phpbb_test_case
{
	private function provider(array $config = []): github
	{
		return new github(new http_client(), new \phpbb\config\config($config + [
			'titaniareleaseimport_github_api'	=> '',
			'titaniareleaseimport_github_web'	=> '',
		]));
	}

	public static function repository_data(): array
	{
		return [
			['https://github.com/phpbbmodders/topicclaim', 'phpbbmodders/topicclaim'],
			['https://github.com/phpbbmodders/topicclaim/', 'phpbbmodders/topicclaim'],
			['https://github.com/phpbbmodders/topicclaim.git', 'phpbbmodders/topicclaim'],
			['github.com/phpbbmodders/topicclaim', 'phpbbmodders/topicclaim'],
			['phpbbmodders/topicclaim', 'phpbbmodders/topicclaim'],
			['  owner/repo.name_x-y  ', 'owner/repo.name_x-y'],
			['https://gitlab.com/owner/repo', null],
			['https://github.com.evil.example/owner/repo', null],
			['https://github.com/owner', null],
			['https://github.com/owner/repo/releases', null],
			['-owner/repo', null],
			['owner/..', null],
			['', null],
		];
	}

	/**
	 * @dataProvider repository_data
	 */
	public function test_parse_repository(string $input, string|null $expected): void
	{
		$this->assertSame($expected, $this->provider()->parse_repository($input));
	}

	public static function release_url_data(): array
	{
		return [
			['https://github.com/owner/repo/releases/tag/v1.2.0', ['repository' => 'owner/repo', 'tag' => 'v1.2.0']],
			['https://github.com/owner/repo/releases/tag/release%2F1.0', ['repository' => 'owner/repo', 'tag' => 'release/1.0']],
			['https://github.com/owner/repo', null],
			['https://example.com/owner/repo/releases/tag/v1', null],
			['v1.2.0', null],
		];
	}

	/**
	 * @dataProvider release_url_data
	 */
	public function test_parse_release_url(string $url, array|null $expected): void
	{
		$this->assertSame($expected, $this->provider()->parse_release_url($url));
	}

	public function test_allowed_prefixes(): void
	{
		$this->assertContains('https://release-assets.githubusercontent.com/', $this->provider()->get_allowed_prefixes());

		// A local stand-in for GitHub, as the feature checks use, allows only itself
		$local = $this->provider(['titaniareleaseimport_github_api' => 'http://127.0.0.1:8199/api', 'titaniareleaseimport_github_web' => 'http://127.0.0.1:8199/web']);
		$this->assertSame(['http://127.0.0.1:8199/api/', 'http://127.0.0.1:8199/web/'], $local->get_allowed_prefixes());
		$this->assertSame('owner/repo', $local->parse_repository('http://127.0.0.1:8199/web/owner/repo'));
	}
}
