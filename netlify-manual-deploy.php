<?php
/**
 * Plugin Name: Netlify Manual Deploy
 * Plugin URI: https://elissavet.dev
 * Description: Adds a custom button to the WordPress Admin Bar to manually trigger a Netlify Build via Webhook. Includes a settings page.
 * Version: 1.1.0
 * Author: Elissavet Triantafyllopoulou
 * Author URI: https://elissavet.dev
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. SETTINGS PAGE CREATION
// ==========================================

add_action('admin_menu', 'elit_netlify_settings_menu');
function elit_netlify_settings_menu() {
    add_options_page(
        'Netlify Deploy Settings', 
        'Netlify Deploy', 
        'manage_options', 
        'elit-netlify-deploy', 
        'elit_netlify_settings_page'
    );
}

add_action('admin_init', 'elit_netlify_register_settings');
function elit_netlify_register_settings() {
    register_setting('elit_netlify_options_group', 'elit_netlify_webhook_url');
}

function elit_netlify_settings_page() {
    ?>
    <div class="wrap">
        <h1>⚙️ Netlify Manual Deploy Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('elit_netlify_options_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Netlify Build Hook URL:</th>
                    <td>
                        <input type="url" name="elit_netlify_webhook_url" 
                               value="<?php echo esc_attr(get_option('elit_netlify_webhook_url')); ?>" 
                               class="regular-text" style="width: 100%; max-width: 600px;" 
                               placeholder="https://api.netlify.com/build_hooks/..." required />
                        <p class="description">Paste your secret Webhook URL from the Netlify Dashboard here.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Hook'); ?>
        </form>
    </div>
    <?php
}

// ==========================================
// 2. ADD BUTTON TO ADMIN BAR
// ==========================================

add_action('admin_bar_menu', 'elit_netlify_deploy_button', 999);
function elit_netlify_deploy_button($wp_admin_bar) {
    if (!current_user_can('manage_options')) return; 

    // Show the button ONLY if the URL is configured
    $webhook_url = get_option('elit_netlify_webhook_url');
    if (empty($webhook_url)) return;

    $wp_admin_bar->add_node([
        'id'    => 'trigger_netlify_deploy',
        'title' => '🚀 Deploy to Netlify',
        'href'  => '#',
        'meta'  => [
            'class'   => 'netlify-deploy-button',
            'onclick' => 'triggerNetlifyDeploy(event);'
        ]
    ]);
}

// ==========================================
// 3. JAVASCRIPT & CSS
// ==========================================

add_action('admin_footer', 'elit_netlify_deploy_js_css');
add_action('wp_footer', 'elit_netlify_deploy_js_css');
function elit_netlify_deploy_js_css() {
    if (!current_user_can('manage_options') || empty(get_option('elit_netlify_webhook_url'))) return;
    ?>
    <style>
        #wp-admin-bar-trigger_netlify_deploy .ab-item {
            background-color: #00ad9f !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }
        #wp-admin-bar-trigger_netlify_deploy:hover .ab-item {
            background-color: #008a7e !important;
        }
    </style>
    <script>
    function triggerNetlifyDeploy(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to deploy all recent changes to Netlify?')) return;

        const data = new FormData();
        data.append('action', 'trigger_netlify_deploy_action');
        data.append('nonce', '<?php echo wp_create_nonce('netlify_deploy_nonce'); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST', body: data
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) alert('✅ Deploy triggered successfully! The site will be live in a few minutes.');
            else alert('❌ Error: ' + (res.data || 'Something went wrong.'));
        })
        .catch(err => {
            alert('❌ Network error.');
        });
    }
    </script>
    <?php
}

// ==========================================
// 4. AJAX SERVER-SIDE HANDLER
// ==========================================

add_action('wp_ajax_trigger_netlify_deploy_action', 'elit_handle_netlify_deploy_action');
function elit_handle_netlify_deploy_action() {
    check_ajax_referer('netlify_deploy_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) wp_send_json_error('You do not have administrator permissions.');

    // Safely retrieve the URL from the database
    $webhook_url = get_option('elit_netlify_webhook_url');
    if (empty($webhook_url)) wp_send_json_error('The Webhook URL is not configured.');

    $response = wp_remote_post($webhook_url, ['blocking' => true, 'timeout' => 10]);

    if (is_wp_error($response)) wp_send_json_error($response->get_error_message());

    $http_code = wp_remote_retrieve_response_code($response);
    
    if ($http_code == 200 || $http_code == 201) wp_send_json_success();
    else wp_send_json_error('Netlify responded with HTTP code: ' . $http_code);
}