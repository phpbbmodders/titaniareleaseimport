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

use phpbbmodders\titaniareleaseimport\exception\release_exception;
use phpbbmodders\titaniareleaseimport\service\importer;

class choose_asset_test extends \phpbb_test_case
{
	private function importer(): importer
	{
		return (new \ReflectionClass(importer::class))->newInstanceWithoutConstructor();
	}

	private function asset(string $name): array
	{
		return ['name' => $name, 'size' => 10, 'url' => 'https://files.example.test/' . $name, 'content_type' => 'application/zip'];
	}

	public function test_single_zip(): void
	{
		$picked = $this->importer()->choose_asset([$this->asset('notes.txt'), $this->asset('ext_1.0.0.zip')], '', 'v1');
		$this->assertSame('ext_1.0.0.zip', $picked['name']);
	}

	public function test_named_zip(): void
	{
		$picked = $this->importer()->choose_asset([$this->asset('a.zip'), $this->asset('b.ZIP')], 'b.ZIP', 'v1');
		$this->assertSame('b.ZIP', $picked['name']);
	}

	public static function error_data(): array
	{
		return [
			'no zip'		=> [['notes.txt'], '', 'TITANIARELEASEIMPORT_ERR_NO_ZIP'],
			'several zips'	=> [['a.zip', 'b.zip'], '', 'TITANIARELEASEIMPORT_ERR_SEVERAL_ZIPS'],
			'unknown name'	=> [['a.zip'], 'c.zip', 'TITANIARELEASEIMPORT_ERR_ASSET_NOT_FOUND'],
		];
	}

	/**
	 * @dataProvider error_data
	 */
	public function test_errors(array $names, string $wanted, string $message): void
	{
		$this->expectException(release_exception::class);
		$this->expectExceptionMessage($message);
		$this->importer()->choose_asset(array_map([$this, 'asset'], $names), $wanted, 'v1');
	}
}
