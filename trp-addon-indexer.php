<?php
/*
Plugin Name: TranslatePress Addon - Indexer
Plugin URI: https://buyreadysite.com/
Description: This addon for TranslatePress automatically indexes the translation tables to improve search performance.
Version: 1.0
Author: BuyReadySite.com
Author URI: https://buyreadysite.com/
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function trp_addon_indexer_menu() {
    add_options_page(
        'TranslatePress Addon - Indexer',
        'TRP Addon - Indexer',
        'manage_options',
        'trp-addon-indexer',
        'trp_addon_indexer_options'
    );
}
add_action('admin_menu', 'trp_addon_indexer_menu');

function trp_addon_indexer_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $logs = get_option('trp_addon_indexer_logs', array());

    echo '<div class="wrap">';
    echo '<h1>TranslatePress Addon - Indexer</h1>';
    echo '<form method="post" action="options.php">';

    settings_fields('trp_addon_indexer_options');
    do_settings_sections('trp-addon-indexer');

    submit_button('Index Now');

    echo '</form>';

    echo '<h2>Logs</h2>';
    echo '<pre>';
    foreach ($logs as $log) {
        echo esc_html($log) . "\n";
    }
    echo '</pre>';

    echo '</div>';
}


function trp_addon_indexer_settings_init() {
    add_settings_section(
        'trp_addon_indexer_section',
        'Settings',
        '',
        'trp-addon-indexer'
    );

    add_settings_field(
        'trp_addon_indexer_lang_prefixes',
        'Language Prefixes',
        'trp_addon_indexer_lang_prefixes_callback',
        'trp-addon-indexer',
        'trp_addon_indexer_section'
    );

    register_setting('trp_addon_indexer_options', 'trp_addon_indexer_lang_prefixes');
}
add_action('admin_init', 'trp_addon_indexer_settings_init');

function trp_addon_indexer_lang_prefixes_callback() {
    $prefixes = esc_attr(get_option('trp_addon_indexer_lang_prefixes'));
    echo "<input type='text' name='trp_addon_indexer_lang_prefixes' value='$prefixes' />";
    echo '<p class="description">Enter the language prefixes separated by comma. For example: en_us,et</p>';
}

function trp_index_translation_tables() {
    if (isset($_POST['trp_addon_indexer_lang_prefixes']) && current_user_can('manage_options')) {
        global $wpdb;
        $prefixes = explode(',', sanitize_text_field($_POST['trp_addon_indexer_lang_prefixes']));
        $logs = array();

        foreach ($prefixes as $prefix) {
            $table_name = $wpdb->prefix . 'trp_dictionary_' . trim($prefix);

            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                $wpdb->query("ALTER TABLE $table_name ADD FULLTEXT(`original`, `translated`)");
                if ($wpdb->last_error) {
                    $logs[] = "Error indexing table $table_name: " . $wpdb->last_error;
                } else {
                    $logs[] = "Successfully indexed table $table_name.";
                }
            } else {
                $logs[] = "Table $table_name does not exist.";
            }
        }

        update_option('trp_addon_indexer_logs', $logs);

        add_action('admin_notices', 'trp_indexing_complete_notice');
    }
}
add_action('admin_init', 'trp_index_translation_tables');


function trp_indexing_complete_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Indexing complete!', 'trp-addon-indexer'); ?></p>
    </div>
    <?php
}
