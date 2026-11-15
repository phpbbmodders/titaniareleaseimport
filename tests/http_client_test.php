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

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use phpbbmodders\titaniareleaseimport\exception\release_exception;
use phpbbmodders\titaniareleaseimport\service\http_client;

class http_client_test extends \phpbb_test_case
{
	private const PREFIXES = ['https://api.example.test/', 'https://files.example.test/'];

	private function client(array $responses): http_client
	{
		return new http_client(new Client(['handler' => HandlerStack::create(new MockHandler($responses))]));
	}

	public function test_follows_allowed_redirects(): void
	{
		$client = $this->client([
			new Response(302, ['Location' => 'https://files.example.test/release.json']),
			new Response(200, ['Content-Type' => 'application/json'], '{"tag_name":"v1"}'),
		]);

		$this->assertSame(['tag_name' => 'v1'], $client->get_json('https://api.example.test/release', self::PREFIXES)['data']);
	}

	public static function refused_data(): array
	{
		return [
			'redirect elsewhere'	=> ['https://api.example.test/release', 'http://169.254.169.254/latest/meta-data/'],
			'look-alike host'		=> ['https://api.example.test/release', 'https://files.example.test.evil.example/x'],
			'credentials in url'	=> ['https://api.example.test/release', 'https://user:pass@files.example.test/x'],
			'start not allowed'		=> ['https://other.example.test/release', ''],
		];
	}

	/**
	 * @dataProvider refused_data
	 */
	public function test_refuses_other_addresses(string $url, string $location): void
	{
		$client = $this->client([new Response(302, ['Location' => $location]), new Response(200)]);

		$this->expectException(release_exception::class);
		$this->expectExceptionMessage('TITANIARELEASEIMPORT_ERR_ADDRESS');
		$client->get_json($url, self::PREFIXES);
	}

	public function test_download_size_limit(): void
	{
		$target = tempnam(sys_get_temp_dir(), 'tri_test_');
		$client = $this->client([new Response(200, ['Content-Length' => '2048'], str_repeat('x', 2048))]);

		try
		{
			$client->download('https://files.example.test/a.zip', self::PREFIXES, 1024, $target);
			$this->fail('A file larger than the limit was accepted');
		}
		catch (release_exception $error)
		{
			$this->assertSame('TITANIARELEASEIMPORT_ERR_TOO_LARGE', $error->getMessage());
		}
		finally
		{
			@unlink($target);
		}
	}
}
