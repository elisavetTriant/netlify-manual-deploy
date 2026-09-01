# Netlify Manual Deploy for WordPress

A lightweight, secure WordPress plugin that adds a custom "Deploy to Netlify" button to the WordPress Admin Bar. Built specifically for Headless WordPress architectures using Netlify Build Hooks.

## Features
* 🚀 **One-Click Deploy:** Trigger Netlify builds manually directly from the WordPress Admin Bar.
* 🧹 **Clear Cache Support:** Includes a secondary button to trigger a full rebuild without cache, directly from WordPress.
* 🏷️ **Live Status Badge:** Displays a real-time Netlify deployment status badge right in the Admin Bar that auto-updates every 10 seconds.
* 🔒 **Enterprise-Grade Security:** Configuration relies strictly on environment variables within `wp-config.php`. It is completely isolated from the database and is never exposed to the front-end or the WordPress UI.
* ⚡ **Zero Bloat:** No external dependencies, just clean PHP and Vanilla JS.

## Installation
1. Download the repository as a `.zip` file.
2. Go to your WordPress Dashboard > **Plugins** > **Add New** > **Upload Plugin**.
3. Upload the `.zip` file and click **Install Now**, then **Activate**.
4. Supply your WordPress installation with your secret Netlify constants. Locate the `wp-config.php` file in your root directory and add the following lines just above the `/* That's all, stop editing! Happy publishing. */` comment:

   ```php
   // Required: Your Netlify Build Hook URL
   define('NETLIFY_BUILD_HOOK_URL', 'https://api.netlify.com/build_hooks/your-secret-hook-id');
   
   // Optional (but recommended): Your Netlify Project ID (found in Site configuration > Site details) to display the Live Status Badge
   define('NETLIFY_SITE_ID', 'your-site-id-string');

## Workflow

Write your posts, update tags, and make all necessary content changes. When you are ready to push the site live, simply click the turquoise Deploy to Netlify button in the Admin Bar!

## 👉 Handling Deletions (The "Soft Delete" Trick)

When using WPGatsby, direct deletions ("Move to Trash") might occasionally be ignored by Gatsby's cache during an incremental build. To ensure deleted content is removed from your live site without having to trigger a slower, full "Clear Cache" build, use the following workflow:

1. Soft Delete: Instead of moving a post directly to the trash, change its status to Draft and click Update.
2. Deploy: Click the standard Deploy button. The Gatsby Action Monitor perfectly registers this status change, removing the content from the live site via a fast incremental build.
3. Trash: Once the deploy is successful, you can safely move the draft to the trash.

(Alternatively, if you move items directly to the trash, you must immediately select "Empty Trash" to force a hard database-level delete event before clicking Deploy).

## Uninstallation
1. Deactivate and delete the plugin via the WordPress Dashboard.
2. Open your root wp-config.php file and manually remove the lines containing NETLIFY_BUILD_HOOK_URL and NETLIFY_SITE_ID to completely clean up your environment.
