# Changelog

## **v0.1.0** - Unreleased

### Added
* **Minn Admin app:** A reimagined WordPress admin served at `/minn-admin/` — a standalone single-page app that talks to the WordPress REST API and lives alongside the classic wp-admin.
* **Overview:** Real stat cards (posts, pages, comments, media storage), an activity chart with 7d/30d/90d ranges, and a recent-activity feed.
* **Content:** Combined posts, pages and custom post types with status pills, author and modified columns, title search, and Load-more pagination.
* **Media:** Grid and list library views with real thumbnails, an Upload button, and drag-and-drop uploads from anywhere in the app. Clicking a file opens a preview overlay (image/video/audio playback, metadata, copy URL, open, delete).
* **Orders:** WooCommerce orders view (when WooCommerce is active) with monthly summary cards, status tabs, and an order detail overlay with line items.
* **Users:** Searchable user directory with roles and registration dates.
* **Comments:** Moderation view with Pending/Approved/Spam/Trash tabs and approve, spam, trash, restore and delete actions, plus a pending-count badge in the sidebar.
* **Extensions:** Activate/deactivate plugins with a switch, update badges, and one-click "Update all".
* **Settings:** General, Writing, Reading and Discussion sections backed by the core settings endpoint, plus a built-in maintenance mode.
* **Editor:** Distraction-free, block-aware writing surface. New posts save native Gutenberg block markup; posts with advanced blocks lock the body read-only with a link to the block editor; classic posts stay classic. Autosave, one-click publish, slash commands (type `/` for headings, quotes, code, lists, images, dividers), image insertion from the media library, editable categories, and post scheduling with a date/time picker. See `docs/editor-direction.md` for the hybrid-editor rationale.
* **Command palette:** ⌘K / Ctrl-K everywhere, with navigation and actions.
* **Notifications:** Pending comments, plugin/core updates and new users with per-user unread tracking.
* **Themes:** Dark and light, persisted per browser. Bundled variable fonts (Hanken Grotesk, JetBrains Mono) — no external font requests.
* **Self-updater:** Update checks against the GitHub manifest with install from GitHub Releases.
