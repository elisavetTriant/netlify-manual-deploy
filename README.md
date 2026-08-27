# Netlify Manual Deploy for WordPress

A lightweight, secure WordPress plugin that adds a custom "Deploy to Netlify" button to the WordPress Admin Bar. Built specifically for Headless WordPress architectures using Netlify Build Hooks.

## Features
* 🚀 **One-Click Deploy:** Trigger Netlify builds manually directly from the WordPress Admin Bar.
* 🔒 **Security:** The Webhook URL is defined strictly as an environment variable within `wp-config.php`. It is completely isolated from the database and is never exposed to the front-end or the WordPress UI.
* ⚡ **Zero Bloat:** No settings pages, no database queries, and no external dependencies, just clean PHP and Vanilla JS.

## Installation
1. Download the repository as a `.zip` file.
2. Go to your WordPress Dashboard > **Plugins** > **Add New** > **Upload Plugin**.
3. Upload the `.zip` file and click **Install Now**, then **Activate**.
4. Supply your WordPress installation with your secret Netlify Build Hook URL. Locate the `wp-config.php` file in your root directory and add the following line just above the `/* That's all, stop editing! Happy publishing. */` comment:

`define('NETLIFY_BUILD_HOOK_URL', 'https://api.netlify.com/build_hooks/your-secret-hook-id');`

## Workflow

Write your posts, update tags, and make all necessary content changes. When you are ready to push the site live, simply click the turquoise Deploy to Netlify button in the Admin Bar!

## Uninstallation
1. Deactivate and delete the plugin via the WordPress Dashboard.
2. Open your root `wp-config.php` file and manually remove the line containing `NETLIFY_BUILD_HOOK_URL` to completely clean up your environment