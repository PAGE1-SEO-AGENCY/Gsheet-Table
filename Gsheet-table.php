<?php
/*
Plugin Name: Gsheet Table by PAGE1 SEO
Description: Hiển thị bảng Google Sheet trong WordPress thông qua shortcode.
Version: 1.0
Author: PAGE1 SEO Agency
Author URI: https://page1.vn
License: GPL2
*/

// Shortcode function to display Google Sheet
function display_google_sheet_table($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
        'range' => 'Sheet1!A1:Z100'
    ), $atts, 'google_sheet_table');

    if (empty($atts['id'])) {
        return 'Vui lòng cung cấp ID của Google Sheet.';
    }

    $sheet_id = $atts['id'];
    $range = $atts['range'];
    $api_key = get_option('google_sheet_api_key'); // Lấy API key từ cài đặt

    if (empty($api_key)) {
        return 'Vui lòng cấu hình API key trong cài đặt plugin.';
    }

    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}/values/{$range}?key={$api_key}";

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return 'Không thể lấy dữ liệu từ Google Sheet.';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['values'])) {
        return 'Không có dữ liệu trong phạm vi này.';
    }

    $html = '<div class="responsive-table"><table class="google-sheet-table">';
    foreach ($data['values'] as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . esc_html($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table></div>';

    return $html;
}

// Register shortcode
add_shortcode('google_sheet_table', 'display_google_sheet_table');

// Add responsive styles and scripts
function google_sheet_table_styles_and_scripts() {
    echo '<style>
        .responsive-table {
            width: 100%;
            overflow-x: auto;
        }
        .google-sheet-table {
            width: 100%;
            border-collapse: collapse;
        }
        .google-sheet-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .google-sheet-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .google-sheet-table tr:hover {
            background-color: #ddd;
        }
        @media screen and (max-width: 600px) {
            .google-sheet-table, .google-sheet-table thead, .google-sheet-table tbody, .google-sheet-table th, .google-sheet-table td, .google-sheet-table tr {
                display: block;
            }
            .google-sheet-table tr {
                border: 1px solid #ccc;
                margin-bottom: 5px;
            }
            .google-sheet-table td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: left;
            }
            .google-sheet-table td:before {
                position: absolute;
                top: 0;
                left: 0;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                content: attr(data-label);
            }
        }
    </style>';
}
add_action('wp_head', 'google_sheet_table_styles_and_scripts');

// Add settings page
add_action('admin_menu', 'google_sheet_table_menu');
function google_sheet_table_menu() {
    add_options_page('Google Sheet Table Settings', 'Google Sheet Table', 'manage_options', 'google-sheet-table', 'google_sheet_table_settings_page');
}

function google_sheet_table_settings_page() {
    ?>
    <div class="wrap">
        <h1>Google Sheet Table Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('google_sheet_table_settings_group'); ?>
            <?php do_settings_sections('google-sheet-table'); ?>
            <?php submit_button(); ?>
        </form>
        <h2>Hướng dẫn sử dụng</h2>
        <p>Để hiển thị bảng Google Sheet trên trang WordPress của bạn, hãy sử dụng shortcode sau:</p>
        <code>[google_sheet_table id="ID_CUA_SHEET" range="Sheet1!A1:Z100"]</code>
        <p>Trong đó:</p>
        <ul>
            <li><strong>id</strong>: ID của Google Sheet.</li>
            <li><strong>range</strong>: Phạm vi dữ liệu muốn lấy (mặc định là <code>Sheet1!A1:Z100</code>).</li>
        </ul>
        <p>@2024 <a href="https://page1.vn" target="_blank">PAGE1 SEO Agency</a></p> <!-- Chèn vào dòng cuối cùng của hàm này -->
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'google_sheet_table_settings_init');
function google_sheet_table_settings_init() {
    register_setting('google_sheet_table_settings_group', 'google_sheet_api_key');

    add_settings_section(
        'google_sheet_table_settings_section',
        'Cài đặt API',
        'google_sheet_table_settings_section_callback',
        'google-sheet-table'
    );

    add_settings_field(
        'google_sheet_api_key',
        'Google API Key',
        'google_sheet_api_key_callback',
        'google-sheet-table',
        'google_sheet_table_settings_section'
    );
}

function google_sheet_table_settings_section_callback() {
    echo 'Nhập API Key của bạn để plugin có thể truy cập Google Sheets.';
}

function google_sheet_api_key_callback() {
    $api_key = get_option('google_sheet_api_key');
    echo '<input type="text" name="google_sheet_api_key" value="' . esc_attr($api_key) . '" size="50">';
}
