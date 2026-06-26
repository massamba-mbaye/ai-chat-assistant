=== AI Chat Assistant ===
Contributors:      massambambaye
Tags:              chatbot, ai, assistant, jokko, support
Requires at least: 6.0
Tested up to:      6.9
Stable tag:        1.6.0
Requires PHP:      7.4
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Add an AI chatbot to any WordPress site, powered by the Jokko AI Cloud service — no AI API key to manage.

== Description ==

AI Chat Assistant adds an AI chatbot to your WordPress site, powered by the **Jokko AI** Cloud service. You don't manage any OpenAI or Anthropic API key: create a Jokko AI account, buy prepaid credits (1 credit = 1 message), paste your account key, and go live in minutes.

How it works: the plugin sends each message to the Jokko AI service, which holds the AI keys server-side, checks your credit balance, calls the AI model, and returns the answer. You only ever paste an **account key**.

= Main Features =

* Powered by the Jokko AI Cloud service — no OpenAI/Anthropic key to handle
* Prepaid credits (1 credit = 1 message) — top up from the Jokko AI dashboard
* Per-site assistant instructions (persona/tone) sent securely to the service
* Floating chat bubble with configurable position, color, and title
* `[ai_chatbot]` shortcode for inline embedding anywhere
* Per-visitor conversation history tracked via UUID cookie
* AES-256-CBC encryption for the account key
* Admin dashboard: settings, conversation list, message logs
* Rate limiting (20 requests/minute per IP) via WordPress transients
* Automatic updates from GitHub Releases
* Multisite compatible
* Zero Composer dependencies — native PHP and WordPress Core only

== Installation ==

1. Upload the `ai-chat-assistant` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** menu in WordPress.
3. Create an account and get your key at https://jokko-ai.im-mass.com/dashboard.php
4. Go to **AI Chatbot > Settings**, paste your account key, write your assistant instructions, and enable the chatbot.

== Frequently Asked Questions ==

= Do I need an OpenAI or Anthropic API key? =

No. The Jokko AI service holds the AI keys for you. You only create a Jokko AI account, buy credits, and paste your account key into the plugin.

= How is it billed? =

Prepaid credits: 1 credit = 1 message. You top up from the Jokko AI dashboard (Wave, Orange Money, Free Money, or card). When credits run out, the chatbot asks you to recharge.

= Does the shortcode work with Elementor, Divi, or Gutenberg? =

Yes. Use `[ai_chatbot]` in any text block or shortcode widget.

= Is the account key stored securely? =

Yes. The Jokko AI account key is encrypted in the database using AES-256-CBC with a key derived from the WordPress `AUTH_KEY` constant.

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

= 1.6.0 =
* Change: billing is now per conversation (1 credit = 1 visitor conversation, all messages included) instead of per message. The plugin sends the visitor conversation key to the Jokko AI service. Balance and wording now read "conversations".

= 1.5.0 =
* New: the settings status banner now shows the remaining Jokko AI credit balance, with a quick "Recharger" link. Read-only, consumes no credit.

= 1.4.2 =
* Change: assistant instructions limit raised from 2000 to 2500 characters.

= 1.4.1 =
* Improve: the "View details" popup on the Plugins page is now richer (Description, Installation, FAQ, full changelog, and metadata), sourced from readme.txt instead of the raw GitHub release notes.

= 1.4.0 =
* New: redesigned settings page with a guided onboarding (status banner + 4 steps: create account, connect key, personalize, activate) and tabbed secondary settings (Assistant, Apparence, Affichage, Avancé).
* New: connection test now updates the status banner and step checkmarks instantly.
* Assistant instructions are now in a dedicated, always-accessible "Assistant" tab.
* All previous widget settings are kept (bubble icon, title, welcome message, color, position, cookie, quick replies, display rules).

= 1.3.1 =
* New: contextual Jokko AI links in the settings — "Créer un compte" when no key is set, "Gérer mes crédits / Recharger" once connected (opens the Jokko AI dashboard).

= 1.3.0 =
* Change: the plugin is now powered exclusively by the Jokko AI Cloud service. The OpenAI and Claude "bring-your-own-key" providers have been removed — you only paste a Jokko AI account key (no AI API key to manage).
* New: per-site "Assistant instructions" field (persona/tone) sent to the service with each message.
* Simplified settings: provider selector, AI keys, model/temperature/mode/Assistants and token settings removed.

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

= 1.6.0 =
Billing is now per conversation. Requires the Jokko AI service to be updated (conversations table + per-conversation logic).

= 1.5.0 =
Shows your remaining credit balance in the settings. Requires the Jokko AI service to be updated (status endpoint).

= 1.4.1 =
Richer plugin details popup on the Plugins page.

= 1.4.0 =
Redesigned, guided settings page. All your existing settings are preserved.

= 1.3.1 =
Adds quick links to create a Jokko AI account or recharge credits from the settings page.

= 1.3.0 =
Major change: the chatbot now runs only through the Jokko AI Cloud service. After updating, go to AI Chatbot > Settings and paste your Jokko AI account key. OpenAI/Claude keys are no longer used.

= 1.1.2 =
Fixes missing Conversations/Logs tables after an update and a PHP warning on the Logs page.

= 1.1.1 =
Fixes the "Nonce invalide" error for logged-in users and streamlines the settings page per provider.

= 1.1.0 =
Adds Claude (Anthropic) support, automatic GitHub updates, and security hardening. Install once manually to enable future automatic updates.

= 1.0.0 =
First stable release.
