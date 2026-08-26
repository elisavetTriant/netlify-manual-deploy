# Netlify Manual Deploy for WordPress

A lightweight, secure WordPress plugin that adds a custom "Deploy to Netlify" button to the WordPress Admin Bar. Built specifically for Headless WordPress architectures using Netlify Build Hooks.

## Features
* 🚀 **One-Click Deploy:** Trigger Netlify builds manually directly from the WordPress Admin Bar.
* ⚙️ **Settings Page:** Easily configure and update your secret Netlify Build Hook URL via the WordPress dashboard (Settings > Netlify Deploy).
* 🔒 **Secure:** Uses native WordPress AJAX and Nonces. The Webhook URL is safely stored in the database and is never exposed to the front-end.
* ⚡ **Zero Bloat:** No external dependencies, just clean PHP and Vanilla JS.

## Installation
1. Download the repository as a `.zip` file.
2. Go to your WordPress Dashboard > **Plugins** > **Add New** > **Upload Plugin**.
3. Upload the `.zip` file and click **Install Now**, then **Activate**.
4. Navigate to **Settings > Netlify Deploy** and paste your Netlify Build Hook URL.

## Workflow
Disable automated webhooks in your Netlify/WordPress setup. Write your posts, update tags, and make all necessary content changes. When you are ready to push the site live, simply click the turquoise **Deploy to Netlify** button in the Admin Bar!