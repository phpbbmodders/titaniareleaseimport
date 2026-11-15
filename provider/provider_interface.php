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

use phpbbmodders\titaniareleaseimport\exception\release_exception;

/**
 * A git hosting service releases can be imported from.
 *
 * Each service is one class in this folder that implements this interface; the
 * provider_loader service finds it there, so adding a service needs no change to
 * existing code. Its constructor receives the extension's http_client and phpBB's
 * config.
 */
interface provider_interface
{
	/**
	 * The service's short name, stored with each linked repository.
	 *
	 * @return string Lower-case letters only, such as "github"
	 */
	public function get_name(): string;

	/**
	 * The service's name as shown to people.
	 *
	 * @return string Such as "GitHub"
	 */
	public function get_label(): string;

	/**
	 * Read a repository from what an author typed: its web address or its short form.
	 *
	 * @param string $input Such as "https://github.com/owner/name" or "owner/name"
	 * @return string|null The repository in the service's short form, or null when it is not one of this service's
	 */
	public function parse_repository(string $input): string|null;

	/**
	 * The repository's web page.
	 *
	 * @param string $repository Short form from parse_repository()
	 * @return string
	 */
	public function get_repository_url(string $repository): string;

	/**
	 * Read a release from the address of its web page.
	 *
	 * @param string $url Such as "https://github.com/owner/name/releases/tag/v1.0.0"
	 * @return array{repository:string,tag:string}|null Null when it is not a release page of this service
	 */
	public function parse_release_url(string $url): array|null;

	/**
	 * Look a release up and list its files.
	 *
	 * @param string $repository Short form from parse_repository()
	 * @param string $tag        The release's tag
	 * @return array{tag:string,name:string,assets:array<int,array{name:string,size:int,url:string,content_type:string}>}
	 * @throws release_exception When the release cannot be found or read
	 */
	public function get_release(string $repository, string $tag): array;

	/**
	 * Address prefixes the service's requests and downloads may use, redirects included.
	 *
	 * @return string[] Such as "https://api.github.com/"
	 */
	public function get_allowed_prefixes(): array;
}
