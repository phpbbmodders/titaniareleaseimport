<?php
/**
 *
 * Titania Release Import extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\titaniareleaseimport\event;

use phpbb\event\data;
use phpbb\language\language;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbbmodders\titaniareleaseimport\exception\release_exception;
use phpbbmodders\titaniareleaseimport\service\importer;
use phpbbmodders\titaniareleaseimport\service\provider_loader;
use phpbbmodders\titaniareleaseimport\service\repository_links;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds the repository link to a contribution's manage page, and the release import
 * to its New Revision page, through Titania's extension events.
 */
class listener implements EventSubscriberInterface
{
	/** @var array{provider:string,repository:string}|null The link checked on submit, saved after Titania's changes */
	private array|null $pending_link = null;

	/** Whether this request imported a release, so the form does not offer it again */
	private bool $imported = false;

	public function __construct(
		private language $language,
		private request_interface $request,
		private template $template,
		private provider_loader $providers,
		private repository_links $links,
		private importer $importer
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'phpbb.titania.contribution_manage_display'		=> 'manage_display',
			'phpbb.titania.contribution_manage_validate'	=> 'manage_validate',
			'phpbb.titania.contribution_manage_submit_after'	=> 'manage_submit_after',
			'phpbb.titania.revision_add_display'			=> 'revision_display',
			'phpbb.titania.revision_create_upload'			=> 'revision_import',
		];
	}

	/**
	 * Show the linked repository on the manage page.
	 *
	 * @param data $event contrib, error
	 */
	public function manage_display(data $event): void
	{
		$this->language->add_lang('common', 'phpbbmodders/titaniareleaseimport');
		$link = $this->links->get((int) $event['contrib']->contrib_id);
		$submitted = $this->request->is_set_post('titaniareleaseimport_repository');
		$selected = $submitted ? $this->request->variable('titaniareleaseimport_provider', '') : ($link['provider'] ?? '');

		$repository = '';
		if ($submitted)
		{
			$repository = $this->request->variable('titaniareleaseimport_repository', '', true);
		}
		else if ($link && ($provider = $this->providers->get($link['provider'])))
		{
			$repository = $provider->get_repository_url($link['repository']);
		}

		foreach ($this->providers->all() as $name => $provider)
		{
			$this->template->assign_block_vars('titaniareleaseimport_providers', [
				'NAME'			=> $name,
				'LABEL'			=> $provider->get_label(),
				'S_SELECTED'	=> $name === $selected,
			]);
		}
		$this->template->assign_vars([
			'TITANIARELEASEIMPORT_REPOSITORY'		=> $repository,
			'S_TITANIARELEASEIMPORT_ONE_PROVIDER'	=> count($this->providers->all()) === 1,
		]);
	}

	/**
	 * Check the repository typed on the manage page.
	 *
	 * @param data $event contrib, error
	 */
	public function manage_validate(data $event): void
	{
		if (!$this->request->is_set_post('titaniareleaseimport_repository'))
		{
			return;
		}

		$this->language->add_lang('common', 'phpbbmodders/titaniareleaseimport');
		$input = trim($this->request->variable('titaniareleaseimport_repository', '', true));
		if ($input === '')
		{
			$this->pending_link = ['provider' => '', 'repository' => ''];
			return;
		}

		$providers = $this->providers->all();
		$provider = $this->providers->get($this->request->variable('titaniareleaseimport_provider', '')) ?? reset($providers);
		$repository = $provider ? $provider->parse_repository($input) : null;
		if ($repository === null)
		{
			$error = $event['error'];
			$error[] = $this->language->lang('TITANIARELEASEIMPORT_ERR_REPOSITORY', $provider ? $provider->get_label() : '');
			$event['error'] = $error;
			return;
		}

		$this->pending_link = ['provider' => $provider->get_name(), 'repository' => $repository];
	}

	/**
	 * Save the repository once Titania saved the contribution.
	 *
	 * @param data $event contrib
	 */
	public function manage_submit_after(data $event): void
	{
		if ($this->pending_link !== null)
		{
			$this->links->set((int) $event['contrib']->contrib_id, $this->pending_link['provider'], $this->pending_link['repository']);
		}
	}

	/**
	 * Offer the import on the New Revision page.
	 *
	 * @param data $event contrib, error
	 */
	public function revision_display(data $event): void
	{
		$this->language->add_lang('common', 'phpbbmodders/titaniareleaseimport');
		$contrib = $event['contrib'];
		$link = $this->links->get((int) $contrib->contrib_id);
		$provider = $link ? $this->providers->get($link['provider']) : null;

		$this->template->assign_vars([
			'S_TITANIARELEASEIMPORT_LINKED'		=> (bool) $provider,
			'TITANIARELEASEIMPORT_PROVIDER'		=> $provider ? $provider->get_label() : '',
			'TITANIARELEASEIMPORT_REPOSITORY'	=> $provider ? $link['repository'] : '',
			'U_TITANIARELEASEIMPORT_REPOSITORY'	=> $provider ? $provider->get_repository_url($link['repository']) : '',
			'U_TITANIARELEASEIMPORT_MANAGE'		=> $contrib->get_url('manage'),
			'TITANIARELEASEIMPORT_RELEASE'		=> $this->imported ? '' : $this->request->variable('titaniareleaseimport_release', '', true),
			'TITANIARELEASEIMPORT_ASSET'		=> $this->imported ? '' : $this->request->variable('titaniareleaseimport_asset', '', true),
		]);
	}

	/**
	 * Import the release the author asked for as the revision's file.
	 *
	 * @param data $event contrib, uploader, import_errors
	 */
	public function revision_import(data $event): void
	{
		$release = trim($this->request->variable('titaniareleaseimport_release', '', true));
		if ($release === '')
		{
			return;
		}

		$this->language->add_lang('common', 'phpbbmodders/titaniareleaseimport');
		try
		{
			// request::variable() returns HTML-escaped text; the importer works on what the author typed
			$imported = $this->importer->import(
				(int) $event['contrib']->contrib_id,
				htmlspecialchars_decode($release, ENT_COMPAT),
				htmlspecialchars_decode(trim($this->request->variable('titaniareleaseimport_asset', '', true)), ENT_COMPAT),
				$event['uploader']
			);
			$this->imported = (bool) $imported;
		}
		catch (release_exception $error)
		{
			// Parameters can come from the code host, such as file names, so they are escaped for the page
			$parameters = array_map(static fn ($value) => is_string($value) ? htmlspecialchars($value, ENT_COMPAT, 'UTF-8') : $value, $error->get_parameters());
			$errors = $event['import_errors'];
			$errors[] = $this->language->lang($error->getMessage(), ...$parameters);
			$event['import_errors'] = $errors;
		}
	}
}
