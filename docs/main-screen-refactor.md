## Refactor Task List: `class-cronicle-admin-main.php`

This task simplifies and organizes the main admin file by splitting responsibilities into dedicated classes and view files.

---

### 1. Review and Identify Responsibilities

Go through `class-cronicle-admin-main.php` and group code into these categories:

- Settings registration and handling
- Admin UI rendering (HTML output)
- Admin menu/page registration
- Script and style enqueueing
- Any API or AJAX handlers

---

### 2. Create Separate Class Files

Inside `includes/admin/`, create these new class files:

- `class-cronicle-settings.php` — for registering and saving plugin settings
- `class-cronicle-ui.php` — for rendering admin page output
- `class-cronicle-enqueue.php` — for enqueueing admin scripts and styles
- `class-cronicle-router.php` — for registering admin menu pages and handling routing

If the file includes logic for AI or external API requests, also create:

- `includes/api/class-cronicle-openai.php` — for handling AI-related API calls

---

### 3. Create a `views/` Folder for Markup

In `includes/admin/views/`, add PHP view templates for large HTML blocks. For example:

- `settings-page.php` — contains the HTML for the settings page

These templates should be loaded from `class-cronicle-ui.php` using `include`.

---

### 4. Move Code to New Classes

For each concern:

- Move the relevant code out of `class-cronicle-admin-main.php`
- Paste it into the corresponding class file and convert to methods
- Use method parameters to pass shared data, or store references in the main plugin class
- Keep each class focused on one job

---

### 5. Instantiate and Register Hooks

In the main plugin file or bootstrap loader:

- Require the new class files
- Instantiate each class
- Call a method (like `register_hooks`) on each to hook it into WordPress

Avoid having logic run automatically on `__construct`

---

### 6. Remove Redundant Code

Once everything is working:

- Remove duplicate or obsolete logic from `class-cronicle-admin-main.php`
- If nothing is left, delete the file

---

### Additional Guidelines

- Follow WordPress coding standards for spacing, escaping, and naming
- Wrap all user-facing text in `__()` or `_e()` for internationalization
- Use `plugin_dir_path()` and `plugin_dir_url()` for loading files and assets
