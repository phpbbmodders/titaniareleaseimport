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
use phpbbmodders\titaniareleaseimport\provider\provider_interface;

/**
 * Finds the git hosting services releases can be imported from.
 *
 * Every class in the extension's provider/ folder that implements provider_interface is
 * one service. Adding a service is adding its file there: nothing else has to change.
 */
class provider_loader
{
	/** @var array<string,provider_interface>|null Keyed by provider name, once loaded */
	private array|null $providers = null;

	public function __construct(
		private http_client $http,
		private config $config,
		private string $provider_dir
	)
	{
	}

	/**
	 * Every available service.
	 *
	 * @return array<string,provider_interface> Keyed by name, sorted by label
	 */
	public function all(): array
	{
		if ($this->providers === null)
		{
			$this->providers = [];
			foreach (glob(rtrim($this->provider_dir, '/') . '/*.php') ?: [] as $file)
			{
				$class = '\\phpbbmodders\\titaniareleaseimport\\provider\\' . basename($file, '.php');
				if (!class_exists($class))
				{
					continue;
				}

				$reflection = new \ReflectionClass($class);
				if ($reflection->isInstantiable() && $reflection->implementsInterface(provider_interface::class))
				{
					/** @var provider_interface $provider */
					$provider = new $class($this->http, $this->config);
					$this->providers[$provider->get_name()] = $provider;
				}
			}
			uasort($this->providers, static fn (provider_interface $a, provider_interface $b): int => strcasecmp($a->get_label(), $b->get_label()));
		}

		return $this->providers;
	}

	/**
	 * One service by name.
	 *
	 * @param string $name
	 * @return provider_interface|null
	 */
	public function get(string $name): provider_interface|null
	{
		return $this->all()[$name] ?? null;
	}

	/**
	 * The service a repository address belongs to.
	 *
	 * @param string $input A repository's address or short form
	 * @return array{provider:provider_interface,repository:string}|null
	 */
	public function find_repository(string $input): array|null
	{
		foreach ($this->all() as $provider)
		{
			$repository = $provider->parse_repository($input);
			if ($repository !== null)
			{
				return ['provider' => $provider, 'repository' => $repository];
			}
		}

		return null;
	}
}
