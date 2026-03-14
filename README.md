# Penkode Headless Framework

**WordPress Headless for projects with a React frontend.**

---

## Project Structure

```
penkode-headless/
├── assets/
│   ├── css/vendor/              # Third-party CSS (Bootstrap, AOS, Animate)
│   ├── fonts/                   # Custom web fonts
│   ├── images/                  # Theme images (logos, placeholders)
│   └── languages/               # Translation files (.mo/.po/.json)
│
├── blocks/
│   └── lucide-icons/            # Custom Gutenberg block (icon picker)
│       ├── block.json
│       ├── editor.css
│       ├── icons.json
│       ├── index.js
│       ├── register.php
│       └── svg/                 # 1800+ Lucide SVG icons
│
├── inc/
│   ├── api/                     # REST API
│   │   ├── api-callbacks.php    # Callback functions for all endpoints
│   │   ├── api-endpoints.php    # Register custom endpoints
│   │   ├── api-utils.php        # API helper functions
│   │   └── revalidation.php     # On-demand cache revalidation (Next.js ISR)
│   │
│   ├── custom-meta/             # Custom Meta Boxes
│   │   ├── custom-fields.php    # Meta fields for Recursos CPT
│   │   └── hero.php             # Hero CPT fields (slides, buttons, backgrounds)
│   │
│   ├── custom-posts/            # Custom Post Types
│   │   ├── post-types.php       # CPT definitions (Noticias, Hero, Recursos, Modales)
│   │   └── utils.php            # CPT helper functions
│   │
│   ├── custom-taxonomies/       # Custom Taxonomies
│   │   └── tax.php              # Taxonomy definitions
│   │
│   ├── dashboard/               # WP Admin Dashboard
│   │   ├── admin/
│   │   │   ├── users.php        # User role restrictions & profile cleanup
│   │   │   └── wp-admin.php     # Admin styles, bar cleanup, dashboard widgets
│   │   ├── editor/
│   │   │   ├── preview.php      # Custom preview redirect to frontend
│   │   │   └── shortcodes.php   # Shortcode definitions
│   │   └── settings/            # Custom Settings page (tabbed)
│   │       ├── analytics.php    # Analytics configuration
│   │       ├── api-endpoints.php # API endpoint settings
│   │       ├── appearance.php   # Appearance settings (front URL, dark mode, etc.)
│   │       ├── chat-bot.php     # AI ChatBot configuration
│   │       ├── home.php         # Home tab (tech stack, dev notes)
│   │       ├── languages.php    # Language settings (WPML)
│   │       ├── posts.php        # Custom post type display settings
│   │       ├── search.php       # Search exclusions (CPTs excluded from search)
│   │       ├── settings-page.php # Main settings page (tabs, menu, JS)
│   │       ├── site-info.php    # Site info (logos, links, social, contact)
│   │       ├── smtp.php         # SMTP mail configuration & diagnostics
│   │       ├── ticker.php       # Ticker/banner configuration
│   │       └── tooltips.php     # Tooltip content management
│   │
│   ├── media/                   # Media utilities
│   │   ├── audio-post.php       # Audio post meta box & media uploader
│   │   ├── text-to-speech.php   # TTS generation (Google Translate)
│   │   └── utils.php            # Image size registration & media helpers
│   │
│   ├── register/                # Theme registration
│   │   └── register-nav-menus.php # Navigation menu locations
│   │
│   ├── security/                # Security hardening
│   │   └── security.php         # CORS, headers, REST restrictions
│   │
│   └── utils/                   # Utility functions
│       ├── include-js.php       # Frontend & admin JS enqueue
│       └── wp-core-utils.php    # WP core tweaks (revisions limit)
│
├── js/
│   ├── admin-media-uploader.js  # WP media uploader for settings fields
│   └── secure-login.js          # Login page security (brute-force protection)
│
├── scss/                        # Dart Sass source files
│   ├── style.scss               # Main entry point (@use for all partials)
│   ├── _variables.scss          # Shared color & layout variables
│   ├── _mixins.scss             # Reusable mixins
│   ├── _login.scss              # Login page styles (dark theme)
│   └── _dashboard.scss          # WP admin & Custom Settings styles
│
├── vendor/                      # Composer dependencies (Google Translate, etc.)
│
├── functions.php                # Theme bootstrap (loads all inc/ files)
├── header.php                   # Minimal header (headless)
├── index.php                    # Blank index (headless)
├── login.php                    # Custom login page template
├── style.css                    # Compiled CSS (Sass output + WP theme header)
├── sass-dev.js                  # Dart Sass build script (dev & production)
├── package.json                 # Node.js config & Sass dependencies
└── .gitignore
```

---

## Custom Settings Page

Located at **Custom Settings** in the WordPress admin sidebar. All settings are organized in tabs:

| Tab | Description | API Endpoint |
|-----|-------------|--------------|
| **Home** | Tech stack overview, developer notes (Sass commands) | — |
| **Analytics** | Analytics configuration | — |
| **API Rest** | API endpoint list & configuration | — |
| **Appearance** | Frontend URL, dark mode, visual settings | — |
| **Chat Bot** | AI ChatBot (name, avatar, prompt, position, color) | `/custom/v1/chatbot` |
| **Languages** | Language settings (WPML integration) | `/custom/v1/languages` |
| **Posts** | Post type display settings | — |
| **Search** | Exclude specific CPTs from WordPress search | — |
| **Site Info** | Logos (light/dark), URLs, social networks, contact info | `/custom/v1/site-info` |
| **SMTP** | SMTP mail config with test & diagnostic buttons | — |
| **Ticker** | Scrolling text banner (text, speed, targeting, size) | `/custom/v1/ticker` |
| **Tooltips** | Tooltip content management with WYSIWYG editor | `/custom/v1/tooltips` |

---

## Custom Post Types

All CPTs support REST API and GraphQL:

| CPT | Slug | Description | Supports |
|-----|------|-------------|----------|
| **Noticias** | `noticias` | News posts | Title, editor, thumbnail, revisions |
| **Hero** | `hero` | Hero slides | Title, thumbnail, revisions |
| **Recursos** | `recursos` | Resources/downloads | Title, editor, thumbnail, revisions |
| **Modales** | `modales` | Popups/modals | Title, editor, thumbnail, revisions |

---

## REST API Endpoints (`/wp-json/custom/v1/`)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/languages` | GET | Active WPML languages |
| `/menus` | GET | All menus or by slug/location |
| `/search` | GET | Custom search (`?term=`) |
| `/post-navigation` | GET | Previous/next post (`?post_id=&post_type=`) |
| `/active-popups` | GET | Active modals |
| `/site-info` | GET | Site configuration (logos, links, social) |
| `/translations/{post_type}/{slug}` | GET | URL translations |
| `/translation/{id}` | GET | Translation by post ID |
| `/custom-fields-schema` | GET | Custom fields schema |
| `/like/{id}` | POST | Increment like count |
| `/likes/{id}` | GET | Get like count |
| `/hero` | GET | Hero data (`?position=&lang=`) |
| `/ticker` | GET | Ticker configuration |
| `/tooltips` | GET | All tooltips |
| `/chatbot` | GET/POST | ChatBot config / Send message |
| `/revalidate` | POST | On-demand ISR revalidation (Next.js) |

---

## Key Features

### Login Page
Custom dark-themed login template (`login.php`) with Penkode brand colors, brute-force protection (rate limiting + lockout), and responsive design.

### Text-to-Speech (TTS)
Automatic MP3 generation for `recursos` and `noticias` posts using Google Translate TTS. Audio files are saved to `/wp-content/uploads/mp3/` and exposed via `audio_url` in the REST API.

### Tooltips System
Create unlimited tooltips in **Custom Settings → Tooltips**. Each tooltip gets a URL (`#tooltip-1`, `#tooltip-2`, etc.) that can be linked in Gutenberg. The frontend renders them on hover.

### ChatBot (AI)
Configurable AI chatbot powered by Groq API. Settings include bot name, avatar, system prompt, position, and primary color.

### Hero System
Custom Post Type for hero slides with title, subtitle, button, background type (image/video/gradient), page targeting, and active toggle.

### Ticker / Banner
Scrolling text banner with enable/disable, WYSIWYG text, link, page targeting, speed, size options, pause on hover, and static mode.

### On-Demand Revalidation
POST endpoint for Next.js ISR. When content changes in WordPress, the frontend cache is revalidated automatically.

### Security
CORS configuration for the frontend domain, REST API access restrictions, input sanitization, and nonce verification.

### WPML Support
Language switcher endpoint, translation endpoints, per-language options (logos, site info), and automatic language detection in API responses.

### Lucide Icons Block
Custom Gutenberg block for inserting Lucide icons directly in the editor. Includes 1800+ SVG icons with a searchable picker.

---

## Sass Workflow (Dart Sass)

Styles are written in SCSS and compiled to `style.css` in the theme root. The WordPress theme header is preserved automatically.

```
scss/
├── style.scss        ← Main entry point (@use for all partials)
├── _variables.scss   ← Shared variables
├── _mixins.scss      ← Reusable mixins
├── _login.scss       ← Login page styles
└── _dashboard.scss   ← WP dashboard styles
```

| Command | What it does |
|---------|--------------|
| `npm run dev` | Watch + live reload via BrowserSync (expanded CSS + sourcemaps) |
| `npm run build` | Production build (compressed CSS, no sourcemaps) |

For detailed setup instructions, see [`plans/SASS-DEV.md`](plans/SASS-DEV.md).

---

## Development

### Adding a Custom Settings Tab

1. Create `inc/dashboard/settings/my-tab.php`
2. Register settings with `register_setting('custom-settings', ...)`
3. Add sections with `add_settings_section()` and fields with `add_settings_field()`
4. In `settings-page.php`: add a `<button>` tab and a `<div>` content area
5. Include the file in `functions.php`

### Adding a REST API Endpoint

1. Add route in `inc/api/api-endpoints.php`
2. Add callback in `inc/api/api-callbacks.php`
3. Add helpers (if needed) in `inc/api/api-utils.php`

### Adding a Custom Post Type

1. Register in `inc/custom-posts/post-types.php`
2. Add meta boxes (if needed) in `inc/custom-meta/`

### Adding New Styles

1. Create `scss/_my-component.scss`
2. Add `@use 'my-component';` in `scss/style.scss`
3. Save — compiles automatically when `npm run dev` is running

---

## Configuration

### package.json (Sass dev)

```json
{
  "sassdev": {
    "proxy": "https://your-site.local",
    "port": 4200
  }
}
```

### Frontend Environment

```env
NEXT_PUBLIC_WORDPRESS_API_URL=https://your-site.local/wp-json
```

---

## License

Custom theme for Penkode projects.
