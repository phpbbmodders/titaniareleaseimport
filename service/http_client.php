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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use phpbbmodders\titaniareleaseimport\exception\release_exception;

/**
 * HTTP requests to git hosting services, limited to the addresses each service allows.
 *
 * Redirects are followed here, not by Guzzle, so every address on the way is checked
 * against the allowed prefixes: the board never fetches from a host the service did
 * not name, such as an address on the board's own network.
 */
class http_client
{
	/** Most redirects followed for one request */
	private const MAX_REDIRECTS = 5;

	/** Seconds allowed to connect, and for a whole request */
	private const CONNECT_TIMEOUT = 10;
	private const TIMEOUT = 60;

	/** Sent with every request; services such as GitHub refuse requests without one */
	private const USER_AGENT = 'phpBB-Titania-Release-Import';

	private Client $client;

	public function __construct(Client|null $client = null)
	{
		$this->client = $client ?? new Client();
	}

	/**
	 * Fetch a JSON document.
	 *
	 * @param string   $url      Address to fetch
	 * @param string[] $prefixes Address prefixes the request and its redirects may use
	 * @param array    $headers  Extra request headers
	 * @return array{status:int,headers:array<string,string>,data:mixed} Headers by lower-case name; data is null when the body is not JSON
	 * @throws release_exception When the service cannot be reached or an address is not allowed
	 */
	public function get_json(string $url, array $prefixes, array $headers = []): array
	{
		$response = $this->request($url, $prefixes, ['headers' => $headers]);

		$names = [];
		foreach ($response->getHeaders() as $name => $values)
		{
			$names[strtolower($name)] = implode(', ', $values);
		}

		return [
			'status'	=> $response->getStatusCode(),
			'headers'	=> $names,
			'data'		=> json_decode((string) $response->getBody(), true),
		];
	}

	/**
	 * Download a file to a path on the server.
	 *
	 * @param string   $url       Address of the file
	 * @param string[] $prefixes  Address prefixes the download and its redirects may use
	 * @param int      $max_bytes Largest file accepted
	 * @param string   $target    Path to write the file to
	 * @throws release_exception When the download fails, is too large, or an address is not allowed
	 */
	public function download(string $url, array $prefixes, int $max_bytes, string $target): void
	{
		$too_large = static function () use ($max_bytes): void
		{
			throw new release_exception('TITANIARELEASEIMPORT_ERR_TOO_LARGE', [get_formatted_filesize($max_bytes)]);
		};

		$response = $this->request($url, $prefixes, [
			'headers'		=> ['Accept' => 'application/octet-stream'],
			'sink'			=> $target,
			'on_headers'	=> static function ($response) use ($max_bytes, $too_large): void
			{
				$length = $response->getHeaderLine('Content-Length');
				if ($response->getStatusCode() === 200 && $length !== '' && (int) $length > $max_bytes)
				{
					$too_large();
				}
			},
			'progress'		=> static function ($total, $downloaded) use ($max_bytes, $too_large): void
			{
				if ($downloaded > $max_bytes)
				{
					$too_large();
				}
			},
		]);

		if ($response->getStatusCode() !== 200 || !is_file($target) || filesize($target) === 0)
		{
			@unlink($target);
			throw new release_exception('TITANIARELEASEIMPORT_ERR_DOWNLOAD');
		}
	}

	/**
	 * Send a GET request, following redirects only to allowed addresses.
	 *
	 * @param string   $url
	 * @param string[] $prefixes
	 * @param array    $options Guzzle request options
	 * @return \Psr\Http\Message\ResponseInterface The final response
	 * @throws release_exception
	 */
	private function request(string $url, array $prefixes, array $options): \Psr\Http\Message\ResponseInterface
	{
		$options = array_merge($options, [
			'allow_redirects'	=> false,
			'http_errors'		=> false,
			'connect_timeout'	=> self::CONNECT_TIMEOUT,
			'timeout'			=> self::TIMEOUT,
		]);
		$options['headers']['User-Agent'] = self::USER_AGENT;

		for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++)
		{
			if (!$this->allowed($url, $prefixes))
			{
				throw new release_exception('TITANIARELEASEIMPORT_ERR_ADDRESS');
			}

			try
			{
				$response = $this->client->request('GET', $url, $options);
			}
			catch (release_exception $error)
			{
				throw $error;
			}
			catch (GuzzleException $error)
			{
				// Guzzle wraps exceptions thrown by the on_headers and progress callbacks
				$previous = $error->getPrevious();
				if ($previous instanceof release_exception)
				{
					throw $previous;
				}
				throw new release_exception('TITANIARELEASEIMPORT_ERR_CONNECT');
			}

			$location = $response->getHeaderLine('Location');
			if ($response->getStatusCode() < 300 || $response->getStatusCode() >= 400 || $location === '')
			{
				return $response;
			}
			$url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
		}

		throw new release_exception('TITANIARELEASEIMPORT_ERR_CONNECT');
	}

	/**
	 * Whether an address starts with one of the allowed prefixes.
	 *
	 * @param string   $url
	 * @param string[] $prefixes
	 * @return bool
	 */
	private function allowed(string $url, array $prefixes): bool
	{
		$parts = parse_url($url);
		if (!$parts || empty($parts['scheme']) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']))
		{
			return false;
		}

		// Compare scheme and host in lower case, as browsers do
		$normal = strtolower($parts['scheme']) . '://' . strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '')
			. ($parts['path'] ?? '/');
		foreach ($prefixes as $prefix)
		{
			if (str_starts_with($normal, strtolower($prefix)))
			{
				return true;
			}
		}

		return false;
	}
}
