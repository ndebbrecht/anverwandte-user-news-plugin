# webtrees User News Plugin

Custom menu/news module for [webtrees](https://www.webtrees.net/). It adds a signed-in-only `Anverwandte` menu with a `News`/`Nachrichten` section. Administrators can create, edit, and delete news items; signed-in users can read them. Guests do not see the menu and cannot access the news page.

The module was originally built for the Anverwandte webtrees installation, but it can be installed as a normal custom webtrees module.

## Features

- Top-level `Anverwandte` menu for signed-in users.
- `News` / `Nachrichten` submenu.
- Menu badge for unread news items.
- Red visual menu indicator when unread items exist.
- Administrator workflow for creating, editing, and deleting news.
- Signed-in-user reading view.
- Filter for all news, unread news, and administrator-authored news.
- Text search across title and body.
- `Go to ID` / `Gehe zu Nr.` field for jumping directly to a news item.
- Visible news numbers in the list.
- New/unread news are marked during the first page view and then counted as read.
- Rich-text editing through the core webtrees HTML editor.
- German and English labels included.
- Decorative Anverwandte tree icon bundled as a module asset.

## Requirements

- webtrees 2.x with custom module support.
- PHP 8.x, as required by your webtrees installation.
- Access to the webtrees installation files.
- Administrator access in webtrees.

No separate database migration is required. The module uses the existing webtrees `news` table. The per-user read state is stored as a webtrees user preference.

## Installation from GitHub

1. Go to your webtrees installation directory on the server.
2. Open the custom modules directory:

   ```bash
   cd /path/to/webtrees/modules_v4
   ```

3. Clone the repository into a directory named `UserNewsPlugin`:

   ```bash
   git clone https://github.com/ndebbrecht/anverwandte-user-news-plugin.git UserNewsPlugin
   ```

4. Make sure the module file exists at:

   ```text
   /path/to/webtrees/modules_v4/UserNewsPlugin/module.php
   ```

5. Sign in to webtrees as an administrator.
6. Open **Control panel -> Modules -> Menus**.
7. Enable **User news** / **Nachrichten**.
8. Keep the menu access restricted to signed-in users. Guests are blocked by the module itself, but the intended setup is a private menu for authenticated users.

## Installation from a ZIP file

If you do not want to use Git on the server:

1. Download the repository as a ZIP file or use a ZIP file sent by email.
2. Extract it locally.
3. Rename the extracted folder to exactly:

   ```text
   UserNewsPlugin
   ```

4. Upload the complete `UserNewsPlugin` folder to:

   ```text
   /path/to/webtrees/modules_v4/UserNewsPlugin
   ```

5. Check that `module.php` is directly inside the `UserNewsPlugin` folder, not one folder deeper.
6. Sign in to webtrees as an administrator.
7. Open **Control panel -> Modules -> Menus**.
8. Enable **User news** / **Nachrichten**.

## Updating

Before updating a live installation, make a backup of:

- the webtrees files,
- the webtrees database,
- any locally changed module files or images.

If installed via Git:

```bash
cd /path/to/webtrees/modules_v4/UserNewsPlugin
git pull
```

If installed via ZIP:

1. Download or receive the new ZIP.
2. Extract it.
3. Rename the folder to `UserNewsPlugin`.
4. Replace the old `modules_v4/UserNewsPlugin` folder with the new one.
5. Re-check that `module.php` is directly inside `UserNewsPlugin`.

After updating, open webtrees as an administrator and verify that the module is still enabled.

## Recommended test after installation

Use a backup or test installation first.

1. Sign in as an administrator.
2. Confirm that the top-level `Anverwandte` menu is visible.
3. Open **Anverwandte -> Nachrichten**.
4. Create a test news item.
5. Confirm that the news item appears in the list.
6. Confirm that the news item has a visible news number.
7. Test search.
8. Test `Go to ID` / `Gehe zu Nr.` with an existing news number.
9. Edit the test news item.
10. Delete the test news item.
11. Sign in as a normal user.
12. Confirm that the user can read news but cannot create, edit, or delete news.
13. Confirm that unread news are visibly marked as `New` / `Neu`.
14. Sign out.
15. Confirm that guests do not see the `Anverwandte` menu and cannot access the module page directly.
16. Confirm that no news block is visible on the public start page unless you intentionally configured a separate webtrees home-page block.

## Public start page and guests

This module hides its menu from guests and denies direct access to unauthenticated visitors.

However, webtrees also has separate home-page blocks. If the standard webtrees news block is enabled on the public start page, guests may still see regular webtrees news there. Disable or remove the public news/home-page block if news must be visible only to signed-in users.

## Data storage

News entries are stored in the standard webtrees `news` table with the current tree ID. The module does not create a separate table.

Unread/read state is tracked per user and tree through a webtrees user preference:

```text
user_news_last_read_<tree-id>
```

Opening the news list marks all currently visible news as read for that user.

## Images in news content

The module uses the core webtrees HTML editor for formatted news content.

It does not include a separate image upload workflow for individual news items. If you want to include images inside a news article, upload the images through your existing server/webtrees workflow and insert the appropriate image URL in the editor.

## Customizing the menu icon

The bundled icon is stored at:

```text
resources/images/anverwandte-tree.png
```

To use a different icon, replace this file with another PNG image using the same filename.

## Troubleshooting

### The module does not appear in the module list

- Check that the folder is named `UserNewsPlugin`.
- Check that `module.php` is directly inside `modules_v4/UserNewsPlugin`.
- Check file permissions so the web server can read the folder.
- Clear any webtrees/server cache if your installation uses one.

### Guests can still see news on the start page

This usually comes from a separate webtrees home-page block, not from this module. Open the webtrees home-page customization and remove or disable the public news block.

### The icon does not load

- Check that `resources/images/anverwandte-tree.png` exists.
- Check the browser network log for a 404 response.
- Make sure the complete `resources` folder was uploaded with the module.

### The rich-text editor does not appear

The module uses the webtrees HTML editor. If the editor fails to load, check whether JavaScript assets are blocked, cached incorrectly, or missing from the webtrees installation.

## Development notes

- Main module class: `UserNewsPluginModule` in `module.php`.
- Views:
  - `resources/views/news/list.phtml`
  - `resources/views/news/edit.phtml`
- Styles:
  - `resources/css/user-news.css`

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
