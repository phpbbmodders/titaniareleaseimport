<?php
/**
 *
 * Titania Release Import extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\titaniareleaseimport\exception;

/**
 * A release could not be imported. The message is a language key, with its parameters,
 * so the author is told why in their own language.
 */
class release_exception extends \phpbb\exception\runtime_exception
{
}
