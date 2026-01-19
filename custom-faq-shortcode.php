<?php
/**
 * Plugin Name: Custom FAQ with Shortcode
 * Description: Add, edit, delete FAQs and display them via shortcode with SEO schema support.
 * Version: 1.3
 * Author: IT Assistance Stockholm
 */

if (!defined('ABSPATH')) exit;

// Register Custom Post Type for FAQ Groups
function csf_register_faq_post_type() {
    register_post_type('csf_faq', array(
        'labels' => array(
            'name' => 'FAQ Groups',
            'singular_name' => 'FAQ Group',
        ),
        'public' => false,
        'show_ui' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-editor-help',
    ));
}
add_action('init', 'csf_register_faq_post_type');

// Add Meta Box for FAQ Items
function csf_add_faq_meta_box() {
    add_meta_box('csf_faq_items', 'FAQ Items', 'csf_faq_meta_box_callback', 'csf_faq', 'normal', 'default');
    add_meta_box('csf_faq_styles', 'FAQ Style Settings', 'csf_faq_styles_meta_box_callback', 'csf_faq', 'side', 'default');
}
add_action('add_meta_boxes', 'csf_add_faq_meta_box');

function csf_faq_meta_box_callback($post) {
    wp_nonce_field('csf_save_faq_items', 'csf_faq_nonce');
    $faqs = get_post_meta($post->ID, '_csf_faq_items', true);
    echo '<div id="csf-faq-container">';
    if (!empty($faqs)) {
        foreach ($faqs as $index => $item) {
            echo '<p><input type="text" name="csf_question[]" value="' . esc_attr($item['q']) . '" placeholder="Question" style="width:45%" />';
            echo '<input type="text" name="csf_answer[]" value="' . esc_attr($item['a']) . '" placeholder="Answer" style="width:45%" /></p>';
        }
    }
    echo '</div>';
    echo '<button type="button" onclick="csf_add_faq_item()">+ Add FAQ</button>';
    echo '<script>
        function csf_add_faq_item() {
            var container = document.getElementById("csf-faq-container");
            var html = `<p><input type=\"text\" name=\"csf_question[]\" placeholder=\"Question\" style=\"width:45%\" /> <input type=\"text\" name=\"csf_answer[]\" placeholder=\"Answer\" style=\"width:45%\" /></p>`;
            container.insertAdjacentHTML("beforeend", html);
        }
    </script>';

    echo '<p><strong>Shortcode:</strong> [my_faq id="' . esc_attr($post->ID) . '"]</p>';
}

function csf_faq_styles_meta_box_callback($post) {
    $style = get_post_meta($post->ID, '_csf_faq_style', true);
    $style = wp_parse_args($style, [
        'font_size' => '18px',
        'font_family' => '',
        'background' => '#f0f0f0',
        'border' => '1px solid #ddd',
        'question_css' => '',
        'answer_css' => ''
    ]);
    echo '<p><label>Font Size:<br><input type="text" name="csf_faq_style[font_size]" value="' . esc_attr($style['font_size']) . '" /></label></p>';
    echo '<p><label>Font Family:<br><input type="text" name="csf_faq_style[font_family]" value="' . esc_attr($style['font_family']) . '" /></label></p>';
    echo '<p><label>Background Color:<br><input type="color" name="csf_faq_style[background]" value="' . esc_attr($style['background']) . '" /></label></p>';
    echo '<p><label>Border:<br><input type="text" name="csf_faq_style[border]" value="' . esc_attr($style['border']) . '" /></label></p>';
    echo '<p><label>Question CSS:<br><textarea name="csf_faq_style[question_css]">' . esc_textarea($style['question_css']) . '</textarea></label></p>';
    echo '<p><label>Answer CSS:<br><textarea name="csf_faq_style[answer_css]">' . esc_textarea($style['answer_css']) . '</textarea></label></p>';
}

function csf_save_faq_items($post_id) {
    if (!isset($_POST['csf_faq_nonce']) || !wp_verify_nonce($_POST['csf_faq_nonce'], 'csf_save_faq_items')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $questions = $_POST['csf_question'] ?? [];
    $answers = $_POST['csf_answer'] ?? [];
    $faq_data = [];
    for ($i = 0; $i < count($questions); $i++) {
        if (!empty($questions[$i]) && !empty($answers[$i])) {
            $faq_data[] = [
                'q' => sanitize_text_field($questions[$i]),
                'a' => wp_kses_post($answers[$i]),
            ];
        }
    }
    update_post_meta($post_id, '_csf_faq_items', $faq_data);

    if (isset($_POST['csf_faq_style'])) {
        update_post_meta($post_id, '_csf_faq_style', array_map('sanitize_text_field', $_POST['csf_faq_style']));
    }
}
add_action('save_post', 'csf_save_faq_items');

function csf_enqueue_faq_assets() {
    wp_enqueue_style('csf-faq-style', plugin_dir_url(__FILE__) . 'css/csf-faq.css');
    wp_enqueue_script('csf-faq-script', plugin_dir_url(__FILE__) . 'js/csf-faq.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'csf_enqueue_faq_assets');

function csf_faq_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts);
    $post_id = (int)$atts['id'];
    $faqs = get_post_meta($post_id, '_csf_faq_items', true);
    $style = get_post_meta($post_id, '_csf_faq_style', true);
    $style = wp_parse_args($style, [
        'font_size' => '18px',
        'font_family' => '',
        'background' => '#f0f0f0',
        'border' => '1px solid #ddd',
        'question_css' => '',
        'answer_css' => ''
    ]);

    if (empty($faqs)) return '';

    $custom_question_style = "font-size: {$style['font_size']}; background: {$style['background']}; border: {$style['border']};";
    if (!empty($style['font_family'])) {
        $custom_question_style .= " font-family: {$style['font_family']};";
    }
    if (!empty($style['question_css'])) {
        $custom_question_style .= ' ' . $style['question_css'];
    }
    $custom_answer_style = !empty($style['answer_css']) ? $style['answer_css'] : '';

    $output = '<div class="csf-faq">';
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "FAQPage",
        "mainEntity" => []
    ];

    foreach ($faqs as $index => $item) {
        $question = wp_kses_post($item['q']);
        $answer = wp_kses_post($item['a']);

        $output .= '<div class="faq-item">';
        $output .= '<button class="faq-question" style="' . esc_attr($custom_question_style) . '" aria-expanded="false"><span class="faq-toggle">+</span> ' . $question . '</button>';
        $output .= '<div class="faq-answer" style="display:none;' . esc_attr($custom_answer_style) . '">' . $answer . '</div>';
        $output .= '</div>';

        $schema['mainEntity'][] = [
            "@type" => "Question",
            "name" => strip_tags($question),
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => $answer
            ]
        ];
    }

    $output .= '</div>';
    $output .= '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    return $output;
}
add_shortcode('my_faq', 'csf_faq_shortcode');
