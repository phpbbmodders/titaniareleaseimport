# Titania Release Import

[![Tests](https://github.com/phpbbmodders/titaniareleaseimport/actions/workflows/tests.yml/badge.svg)](https://github.com/phpbbmodders/titaniareleaseimport/actions/workflows/tests.yml) [![Lint](https://github.com/phpbbmodders/titaniareleaseimport/actions/workflows/lint.yml/badge.svg)](https://github.com/phpbbmodders/titaniareleaseimport/actions/workflows/lint.yml)

Lets contribution authors add a new Titania revision straight from a GitHub release.

## Features

- **Source repository** on a contribution's **Manage** page: link the GitHub repository its releases are published in.
- **Import from a release** on the **New Revision** page: enter a release's tag, such as `v1.2.0`, or the address of its release page, instead of uploading a file. The release's ZIP file becomes the revision's file.
- The imported file goes through the same checks as an upload: Titania's allowed file types, its size limit, repacking and the validation queue.
- Only releases of the linked repository are imported. When a release has more than one ZIP file, the author names the one to use.
- The board only downloads from GitHub's own addresses, and follows redirects only to them, so it cannot be pointed at other hosts or at its own network.
- Each git hosting service is one file in `provider/`. GitHub is the only one so far; another service is added as a new file there, without changing the existing code.

## Requirements

- phpBB 4.0
- PHP 8.2 or later
- [Titania](https://github.com/phpbb/customisation-db) (the Customisation Database) with the extension events this extension uses. They are on the `4.0.x-phpbb4-fixes` branch of the [phpbbmodders fork](https://github.com/phpbbmodders/customisation-db), and not yet in Titania itself.
- The board must be able to reach `api.github.com`, `github.com` and GitHub's file hosts over HTTPS.

Releases are read from GitHub's public API without a token, which GitHub limits to 60 requests an hour for each board.

## Installation

1. Enable Titania first.
2. Copy the extension to `/ext/phpbbmodders/titaniareleaseimport`
3. In the Administration Control Panel, go to **Customise → Manage extensions**
4. Enable the **Titania Release Import** extension

## Contributing

Contributions are welcome!

- **Bug reports**: [Open an issue](https://github.com/phpbbmodders/titaniareleaseimport/issues).
- **Everything else** (questions, feature requests, ideas, general discussion): [Use Discussions](https://github.com/orgs/phpbbmodders/discussions), or the [community forum](https://www.phpbbmodders.com/community/).
- Pull requests are welcome for bug fixes or discussed features.

## Acknowledgments

- Code, tests and documentation by [Claude](https://www.anthropic.com/claude).

## License

This extension is licensed under the **GNU General Public License v2.0**.

See [license.txt](license.txt) for more information.
