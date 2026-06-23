=== AI Chat Assistant ===
Contributors:      massambambaye
Tags:              chatbot, openai, claude, anthropic, ai
Requires at least: 6.0
Tested up to:      6.9
Stable tag:        1.2.0
Requires PHP:      7.4
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Add an OpenAI- or Claude-powered chatbot to any WordPress site with a floating bubble or inline shortcode.

== Description ==

AI Chat Assistant integrates an AI chatbot directly into your WordPress site, powered by **OpenAI** or **Anthropic Claude**. Pick your provider, configure everything from the admin dashboard, and go live in minutes.

Two providers:

* **OpenAI** — Chat Completions (`/v1/chat/completions`, fast and flexible) and Assistants API (`/v1/assistants` + threads, persistent memory on the OpenAI side).
* **Claude (Anthropic)** — Messages API (`/v1/messages`) with models such as Claude Haiku 4.5, Sonnet 4.6, and Opus 4.8.

Configure a separate API key for each provider and switch the active provider at any time.

= Main Features =

* Two AI providers (OpenAI and Claude) selectable from the settings page
* Floating chat bubble with configurable position, color, and title
* `[ai_chatbot]` shortcode for inline embedding anywhere
* Per-visitor conversation history tracked via UUID cookie
* AES-256-CBC encryption for each API key and the system prompt
* Full admin dashboard: settings, conversation list, API logs (model + token usage)
* Rate limiting (20 requests/minute per IP) via WordPress transients
* Automatic updates from GitHub Releases
* Multisite compatible
* Zero Composer dependencies — native PHP and WordPress Core only

== Installation ==

1. Upload the `ai-chat-assistant` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** menu in WordPress.
3. Go to **AI Chatbot > Settings**, choose your provider, and enter the matching API key (OpenAI and/or Claude).
4. Enable the chatbot and configure the widget to your preferences.

== Frequently Asked Questions ==

= Which providers and models are supported? =

OpenAI — Chat Completions: `gpt-4o`, `gpt-4o-mini`, `gpt-4-turbo`, `gpt-4`, `gpt-3.5-turbo`; Assistants API: any assistant created in your OpenAI account.
Claude (Anthropic) — Messages API: `claude-haiku-4-5`, `claude-sonnet-4-6`, `claude-opus-4-8`.

= Where do I get the API keys? =

OpenAI keys: platform.openai.com (API keys). Claude keys: console.anthropic.com (Settings > API Keys); the Anthropic API is billed separately from a Claude.ai subscription and requires prepaid credits.

= Does the shortcode work with Elementor, Divi, or Gutenberg? =

Yes. Use `[ai_chatbot]` in any text block or shortcode widget.

= Is the API key stored securely? =

Yes. Each key is encrypted in the database using AES-256-CBC with a key derived from the WordPress `AUTH_KEY` constant.

= My site is behind Cloudflare or a load balancer — how is the rate limit counted? =

By default the rate limit uses the direct connection IP (`REMOTE_ADDR`), which cannot be spoofed. If your site sits behind a trusted reverse proxy / CDN, return true on the `waicb_trust_proxy_headers` filter to honor forwarded-for headers instead.

= How do I temporarily disable the chatbot? =

In **AI Chatbot > Settings**, uncheck "Enable chatbot". The widget disappears immediately without deactivating the plugin.

== Screenshots ==

1. Frontend chatbot widget (bubble + panel)
2. Settings page (admin dashboard)
3. Conversation list
4. API logs (model + token usage)

== Changelog ==

= 1.2.0 =
* New: "Cloud" provider — connect the chatbot to a prepaid credits SaaS (e.g. Jokko AI) instead of your own OpenAI/Anthropic key. The site sends only an account key; the SaaS holds the AI keys, checks credits, and bills 1 credit per message.
* New: settings section for the Cloud provider (proxy URL + account key) with a connection test that validates the key without consuming a credit.

= 1.1.2 =
* Fix: database tables are now self-healing — they are (re)created automatically on update if missing, instead of only on activation. Fixes empty Conversations/Logs pages and lost chat history after an in-place update.
* Fix: "Trying to access array offset on null" warning on the Logs page when no logs exist yet.

= 1.1.1 =
* Fix: "Nonce invalide" error when sending a message while logged in — the chat request now authenticates the cookie session via the WordPress REST nonce (X-WP-Nonce).
* UX: the settings page now shows only the active provider's options (OpenAI or Claude), and the OpenAI engine reveals either Chat Completion or Assistants API fields. Shared generation settings (system prompt, max tokens, history) are grouped separately.

= 1.1.0 =
* New: Claude (Anthropic) provider via the Messages API, alongside OpenAI.
* New: provider selector with a separate, encrypted API key per provider.
* New: automatic updates from GitHub Releases.
* Security: rate limiting now uses the direct connection IP by default (ignores spoofable forwarded-for headers unless the `waicb_trust_proxy_headers` filter opts in).
* Security: hardened the frontend Markdown renderer against malformed links and iframes.
* Change: API logs now show model and token usage only (no estimated USD cost).
* Internal: code cleanup and de-duplication.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.2 =
Fixes missing Conversations/Logs tables after an update and a PHP warning on the Logs page.

= 1.1.1 =
Fixes the "Nonce invalide" error for logged-in users and streamlines the settings page per provider.

= 1.1.0 =
Adds Claude (Anthropic) support, automatic GitHub updates, and security hardening. Install once manually to enable future automatic updates.

= 1.0.0 =
First stable release.
