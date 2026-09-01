<?php
/**
 * Plugin Name: Netlify Manual Deploy
 * Plugin URI: https://elissavet.dev
 * Description: Secure Admin Bar buttons to trigger Netlify Builds (Standard & Clear Cache) and a live Status Badge. Configured via wp-config.php.
 * Version: 1.3.0
 * Author: Elissavet Triantafyllopoulou
 * Author URI: https://elissavet.dev
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. ADD BUTTONS & BADGE TO ADMIN BAR
// ==========================================

add_action('admin_bar_menu', 'elit_netlify_deploy_ui', 999);
function elit_netlify_deploy_ui($wp_admin_bar) {
    if (!current_user_can('manage_options')) return; 

    // Ensure the required webhook constant exists in wp-config.php
    if (!defined('NETLIFY_BUILD_HOOK_URL') || empty(NETLIFY_BUILD_HOOK_URL)) return;

    // 1. The Status Badge (if the Site ID is configured)
    if (defined('NETLIFY_SITE_ID') && !empty(NETLIFY_SITE_ID)) {
        $badge_url = 'https://api.netlify.com/api/v1/badges/' . NETLIFY_SITE_ID . '/deploy-status';
        
        $wp_admin_bar->add_node([
            'id'    => 'netlify_deploy_status_badge',
            'title' => '<img id="netlify-status-badge" src="' . esc_url($badge_url) . '" alt="Netlify Status" style="vertical-align: middle; height: 20px; margin-top: -3px;" />',
            'href'  => '#',
            'meta'  => ['class' => 'netlify-badge-node']
        ]);
    }

    // 2. The Standard Deploy Button (Turquoise)
    $wp_admin_bar->add_node([
        'id'    => 'trigger_netlify_deploy_standard',
        'title' => '🚀 Deploy',
        'href'  => '#',
        'meta'  => [
            'class'   => 'netlify-deploy-button-standard',
            'onclick' => 'triggerNetlifyDeploy(event, false);'
        ]
    ]);

    // 3. The Clear Cache Deploy Button (Red)
    $wp_admin_bar->add_node([
        'id'    => 'trigger_netlify_deploy_clear',
        'title' => '🧹 Deploy (Clear Cache)',
        'href'  => '#',
        'meta'  => [
            'class'   => 'netlify-deploy-button-clear',
            'onclick' => 'triggerNetlifyDeploy(event, true);'
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
        #wp-admin-bar-netlify_deploy_status_badge .ab-item { pointer-events: none; }
        
        #wp-admin-bar-trigger_netlify_deploy_standard .ab-item {
            background-color: #00ad9f !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }
        #wp-admin-bar-trigger_netlify_deploy_standard:hover .ab-item { background-color: #008a7e !important; }
        
        #wp-admin-bar-trigger_netlify_deploy_clear .ab-item {
            background-color: #e63946 !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: background-color 0.2s ease;
            margin-left: 5px;
        }
        #wp-admin-bar-trigger_netlify_deploy_clear:hover .ab-item { background-color: #c12935 !important; }
    </style>
    <script>
    // Auto-refresh the Netlify Status Badge every 10 seconds
    setInterval(function() {
        const badge = document.getElementById('netlify-status-badge');
        if (badge) {
            const currentSrc = badge.src.split('?')[0];
            badge.src = currentSrc + '?t=' + new Date().getTime(); // Force bypass browser cache
        }
    }, 10000);

    function triggerNetlifyDeploy(e, clearCache) {
        e.preventDefault();
        
        const confirmMsg = clearCache 
            ? '⚠️ Are you sure you want to CLEAR CACHE and deploy? This will trigger a full rebuild.'
            : 'Are you sure you want to deploy all recent changes to Netlify?';

        if (!confirm(confirmMsg)) return;

        const data = new FormData();
        data.append('action', 'trigger_netlify_deploy_action');
        data.append('nonce', '<?php echo wp_create_nonce('netlify_deploy_nonce'); ?>');
        data.append('clear_cache', clearCache ? '1' : '0');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST', body: data
        })
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                alert('❌ Error: ' + (res.data || 'Something went wrong.'));
            }
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

    if (!defined('NETLIFY_BUILD_HOOK_URL') || empty(NETLIFY_BUILD_HOOK_URL)) {
        wp_send_json_error('The Webhook URL is not configured in wp-config.php.');
    }

    $webhook_url = NETLIFY_BUILD_HOOK_URL;
    $clear_cache = isset($_POST['clear_cache']) && $_POST['clear_cache'] === '1';

    // Append the clear_cache parameter to force a clean build on Netlify
    if ($clear_cache) {
        $webhook_url = add_query_arg('clear_cache', 'true', $webhook_url);
    }

    $response = wp_remote_post($webhook_url, ['blocking' => true, 'timeout' => 10]);

    if (is_wp_error($response)) wp_send_json_error($response->get_error_message());

    $http_code = wp_remote_retrieve_response_code($response);
    
    if ($http_code == 200 || $http_code == 201) wp_send_json_success();
    else wp_send_json_error('Netlify responded with HTTP code: ' . $http_code);
}