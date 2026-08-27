<?php
/**
 * Plugin Name: Netlify Manual Deploy
 * Plugin URI: https://elissavet.dev
 * Description: Adds a custom button to the WordPress Admin Bar to manually trigger a Netlify Build via Webhook. Securely configured via wp-config.php.
 * Version: 1.1.1
 * Author: Elissavet Triantafyllopoulou
 * Author URI: https://elissavet.dev
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. ADD BUTTON TO ADMIN BAR
// ==========================================

add_action('admin_bar_menu', 'elit_netlify_deploy_button', 999);
function elit_netlify_deploy_button($wp_admin_bar) {
    if (!current_user_can('manage_options')) return; 

    // Εμφάνιση του κουμπιού ΜΟΝΟ αν έχει δηλωθεί η σταθερά στο wp-config.php
    if (!defined('NETLIFY_BUILD_HOOK_URL') || empty(NETLIFY_BUILD_HOOK_URL)) return;

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
// 2. JAVASCRIPT & CSS
// ==========================================

add_action('admin_footer', 'elit_netlify_deploy_js_css');
add_action('wp_footer', 'elit_netlify_deploy_js_css');
function elit_netlify_deploy_js_css() {
    if (!current_user_can('manage_options') || !defined('NETLIFY_BUILD_HOOK_URL')) return;
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
            console.error(err);
        });
    }
    </script>
    <?php
}

// ==========================================
// 3. AJAX SERVER-SIDE HANDLER
// ==========================================

add_action('wp_ajax_trigger_netlify_deploy_action', 'elit_handle_netlify_deploy_action');
function elit_handle_netlify_deploy_action() {
    check_ajax_referer('netlify_deploy_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) wp_send_json_error('You do not have administrator permissions.');

    // Ασφαλής ανάκτηση του URL από το wp-config.php
    if (!defined('NETLIFY_BUILD_HOOK_URL') || empty(NETLIFY_BUILD_HOOK_URL)) {
        wp_send_json_error('The Webhook URL is not configured in wp-config.php.');
    }

    $webhook_url = NETLIFY_BUILD_HOOK_URL;

    $response = wp_remote_post($webhook_url, ['blocking' => true, 'timeout' => 10]);

    if (is_wp_error($response)) wp_send_json_error($response->get_error_message());

    $http_code = wp_remote_retrieve_response_code($response);
    
    if ($http_code == 200 || $http_code == 201) wp_send_json_success();
    else wp_send_json_error('Netlify responded with HTTP code: ' . $http_code);
}