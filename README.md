# AI Chat Assistant

[![Lint](https://github.com/massamba-mbaye/ai-chat-assistant/actions/workflows/lint.yml/badge.svg)](https://github.com/massamba-mbaye/ai-chat-assistant/actions/workflows/lint.yml)
[![WordPress Compatibility](https://github.com/massamba-mbaye/ai-chat-assistant/actions/workflows/wp-compatibility.yml/badge.svg)](https://github.com/massamba-mbaye/ai-chat-assistant/actions/workflows/wp-compatibility.yml)
[![License: GPL v2+](https://img.shields.io/badge/license-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%E2%80%93latest-21759b.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%E2%80%938.3-777bb4.svg)](https://php.net)

> Add an OpenAI- or Claude-powered chatbot to any WordPress site — floating bubble or inline shortcode, with a full admin dashboard and conversation history.

**Plugin homepage:** [im-mass.com/plugins/ai-chat-assistant](https://www.im-mass.com/plugins/ai-chat-assistant)

---

## Features

- **Two AI providers** in one plugin, switchable from the settings page:
  - **OpenAI** — Chat Completions (`/v1/chat/completions`, supports `gpt-4o`, `gpt-4o-mini`, `gpt-4-turbo`, `gpt-4`, `gpt-3.5-turbo`) and Assistants API (`/v1/assistants` + threads, persistent memory on OpenAI's side)
  - **Claude (Anthropic)** — Messages API (`/v1/messages`, supports `claude-haiku-4-5`, `claude-sonnet-4-6`, `claude-opus-4-8`)
- **Separate, encrypted API key per provider**
- **Floating chat bubble** with configurable position, color, and title
- **`[ai_chatbot]` shortcode** for inline embedding (Elementor, Divi, Gutenberg compatible)
- **Per-visitor conversation history** tracked via UUID cookie
- **AES-256-CBC encryption** for each API key and the system prompt
- **Admin dashboard** : settings, conversation viewer, API logs (model + token usage)
- **Rate limiting** (20 requests/minute per IP) via WordPress transients
- **Automatic updates** from GitHub Releases
- **Multisite compatible**
- **Zero Composer dependencies** — native PHP and WordPress Core only

## Requirements

| Component | Minimum | Tested up to |
|---|---|---|
| WordPress | 6.0 | latest |
| PHP | 7.4 | 8.3 |
| OpenAI API key ([platform.openai.com](https://platform.openai.com)) | for OpenAI provider | — |
| Claude API key ([console.anthropic.com](https://console.anthropic.com)) | for Claude provider | — |

At least one provider key is required. The Anthropic API is billed separately from a Claude.ai subscription and requires prepaid credits.

Compatibility verified automatically via GitHub Actions on **19 environments** (WP 6.0 / 6.4 / 6.5 / 6.6 / latest × PHP 7.4 / 8.0 / 8.1 / 8.2).

## Installation

### From GitHub Release (recommended)

1. Download the latest `ai-chat-assistant.zip` from the [Releases page](https://github.com/massamba-mbaye/ai-chat-assistant/releases/latest)
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Select the `.zip` and click **Install Now**, then **Activate**
4. Go to **AI Chatbot → Settings** and enter your OpenAI API key
5. Enable the chatbot and configure the widget

### From source (developers)

```bash
cd wp-content/plugins/
git clone https://github.com/massamba-mbaye/ai-chat-assistant.git
```

Then activate via the WordPress admin.

## Usage

### Floating bubble
Enable it in **AI Chatbot → Settings → Display options**. It appears on every front-end page.

### Inline shortcode
Drop this anywhere a shortcode is allowed:

```
[ai_chatbot]
```

Works in posts, pages, widgets, Elementor text blocks, Divi modules, Gutenberg blocks.

## Security

- Each API key is **encrypted at rest** using AES-256-CBC with a key derived from the WordPress `AUTH_KEY` constant.
- All AJAX endpoints are protected by **WordPress nonces**.
- **Rate limiting** (20 req/min per IP) prevents abuse. The client IP is taken from the direct connection (`REMOTE_ADDR`) by default; behind a trusted reverse proxy / CDN, opt into forwarded-for headers via the `waicb_trust_proxy_headers` filter.
- The frontend Markdown renderer sanitizes links and only allows trusted (Google Maps) iframes, rebuilt from a clean template.
- No third-party libraries → reduced supply-chain surface.

## Updates

The plugin checks **GitHub Releases** for new versions and shows updates in the WordPress admin like any other plugin. Each release attaches an `ai-chat-assistant.zip` asset. Note: the very first upgrade onto a release that includes the updater must be installed manually; subsequent updates are automatic.

## Development

### Project structure

```
ai-chat-assistant/
├── admin/           # Admin pages, assets, views
├── includes/        # Core classes (chat, assistants, DB, security, etc.)
├── public/          # Frontend widget + assets
├── languages/       # i18n .pot file
├── ai-chat-assistant.php
├── readme.txt       # WordPress.org format
└── uninstall.php
```

### Running CI locally

The CI runs `php -l` and PHPCS with the WordPress standard. To replicate:

```bash
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
composer global require wp-coding-standards/wpcs dealerdirect/phpcodesniffer-composer-installer
phpcs --standard=WordPress --ignore=vendor/,node_modules/,languages/ .
```

## Contributing

Issues and pull requests welcome. Please:
1. Open an issue first to discuss significant changes
2. Make sure CI passes (PHP syntax + plugin activation across all WP/PHP versions)
3. Follow WordPress Coding Standards where practical

## License

GPL v2 or later — see [LICENSE](LICENSE).

## Author

**Massamba MBAYE**
[LinkedIn](https://www.linkedin.com/in/massamba-mbaye/) · [im-mass.com](https://www.im-mass.com)
