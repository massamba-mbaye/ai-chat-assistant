=== AI Chat Assistant ===
Contributors:      massambambaye
Tags:              chatbot, openai, ai, chat, gpt
Requires at least: 6.0
Tested up to:      6.9
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Add an OpenAI-powered chatbot (Chat Completions and Assistants API) to any WordPress site with a floating bubble or inline shortcode.

== Description ==

AI Chat Assistant integrates an OpenAI-powered chatbot directly into your WordPress site. Choose between two engines, configure everything from the admin dashboard, and go live in minutes.

Two configurable engines:

* **Chat Completions** (`/v1/chat/completions`) — default mode, fast and flexible.
* **Assistants API** (`/v1/assistants` + threads) — advanced mode with persistent memory on the OpenAI side.

= Main Features =

* Floating chat bubble with configurable position, color, and title
* `[ai_chatbot]` shortcode for inline embedding anywhere
* Per-visitor conversation history tracked via UUID cookie
* AES-256-CBC encryption for the API key and system prompt
* Full admin dashboard: settings, conversation list, API logs with cost tracking
* Rate limiting (20 requests/minute per IP) via WordPress transients
* Multisite compatible
* Zero Composer dependencies — native PHP and WordPress Core only

== Installation ==

1. Upload the `wordpress-ai-chatbot` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** menu in WordPress.
3. Go to **AI Chatbot > Settings** and enter your OpenAI API key.
4. Enable the chatbot and configure the widget to your preferences.

== Frequently Asked Questions ==

= Which OpenAI models are supported? =

Chat Completions mode: `gpt-4o`, `gpt-4o-mini`, `gpt-4-turbo`, `gpt-4`, `gpt-3.5-turbo`.
Assistants API mode: any assistant created in your OpenAI account.

= Does the shortcode work with Elementor, Divi, or Gutenberg? =

Yes. Use `[ai_chatbot]` in any text block or shortcode widget.

= Is the API key stored securely? =

Yes. It is encrypted in the database using AES-256-CBC with a key derived from the WordPress `AUTH_KEY` constant.

= How do I temporarily disable the chatbot? =

In **AI Chatbot > Settings**, uncheck "Enable chatbot". The widget disappears immediately without deactivating the plugin.

== Screenshots ==

1. Frontend chatbot widget (bubble + panel)
2. Settings page (admin dashboard)
3. Conversation list
4. API logs with cost tracking

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
First stable release.
