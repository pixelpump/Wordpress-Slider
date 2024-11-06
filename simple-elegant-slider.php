<?php
/*
Plugin Name: Simple Elegant Slider w/video
Description: An elegant, simple image slider plugin with mobile swipe support
Version: 1.2
Author: Daniel Doucette
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SimpleElegantSlider {
    private $sliders;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_init', array($this, 'handle_slider_deletion')); // Handle deletion here
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_shortcode('simple_elegant_slider', array($this, 'slider_shortcode'));
        
        $this->sliders = get_option('simple_elegant_sliders', array());
    }

    public function add_plugin_page() {
        add_menu_page(
            'Simple Elegant Slider',
            'SE Slider',
            'manage_options',
            'simple-elegant-slider',
            array($this, 'create_admin_page'),
            'dashicons-images-alt2'
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Simple Elegant Slider Settings</h1>
            <?php
            if (isset($_GET['edit']) && isset($this->sliders[$_GET['edit']])) {
                $this->edit_slider_form($_GET['edit']);
            } else {
                $this->sliders_list();
            }
            ?>
        </div>
        <?php
    }

    private function sliders_list() {
        ?>
        <h2>Your Sliders</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Slider Name</th>
                    <th>Shortcode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->sliders as $name => $slider): ?>
                <tr>
                    <td><?php echo esc_html($name); ?></td>
                    <td><code>[simple_elegant_slider name="<?php echo esc_attr($name); ?>"]</code></td>
                    <td>
                        <a href="?page=simple-elegant-slider&edit=<?php echo esc_attr($name); ?>" class="button">Edit</a>
                        <a href="?page=simple-elegant-slider&action=delete&name=<?php echo esc_attr($name); ?>" class="button" onclick="return confirm('Are you sure you want to delete this slider?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h2>Add New Slider</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('simple_elegant_slider_group');
            do_settings_sections('simple-elegant-slider');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Show Captions</th>
                    <td>
                        <input type="checkbox" id="show_captions" name="simple_elegant_sliders[show_captions]" value="1" />
                        <label for="show_captions">Show image captions</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Caption Text Color</th>
                    <td>
                        <input type="text" id="caption_text_color" name="simple_elegant_sliders[caption_text_color]" value="#ffffff" class="color-field" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Animation Speed (ms)</th>
                    <td>
                        <input type="number" id="animation_speed" name="simple_elegant_sliders[animation_speed]" value="500" min="100" step="100" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Add Slider'); ?>
        </form>
        <?php
    }

    private function edit_slider_form($name) {
        $slider = $this->sliders[$name];
        ?>
        <h2>Edit Slider: <?php echo esc_html($name); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('simple_elegant_slider_group');
            ?>
            <input type="hidden" name="slider_edit_name" value="<?php echo esc_attr($name); ?>">
            <table class="form-table">
                <tr>
                    <th scope="row">Slider Media</th>
                    <td>
                        <input type="hidden" id="slider_media" name="slider_media" value="<?php echo esc_attr(implode(',', $slider['media'])); ?>" />
                        <button type="button" class="button" id="select_slider_media">Select Media</button>
                        <div id="slider_media_preview">
                            <?php
                            foreach ($slider['media'] as $media_id) {
                                $mime_type = get_post_mime_type($media_id);
                                if (strpos($mime_type, 'video') !== false) {
                                    echo '<video src="' . esc_url(wp_get_attachment_url($media_id)) . '" style="max-width:100px;max-height:100px;margin-right:10px;" controls></video>';
                                } else {
                                    echo wp_get_attachment_image($media_id, 'thumbnail', false, array('style' => 'max-width:100px;max-height:100px;margin-right:10px;'));
                                }
                            }
                            ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto Slide</th>
                    <td>
                        <input type="checkbox" id="auto_slide" name="auto_slide" value="1" <?php checked(isset($slider['auto_slide']) && $slider['auto_slide']); ?> />
                        <label for="auto_slide">Enable automatic sliding</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Show Captions</th>
                    <td>
                        <input type="checkbox" id="show_captions" name="show_captions" value="1" <?php checked(isset($slider['show_captions']) && $slider['show_captions']); ?> />
                        <label for="show_captions">Show image captions</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Caption Text Color</th>
                    <td>
                        <input type="text" id="caption_text_color" name="caption_text_color" value="<?php echo esc_attr($slider['caption_text_color'] ?? '#ffffff'); ?>" class="color-field" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Animation Speed (ms)</th>
                    <td>
                        <input type="number" id="animation_speed" name="animation_speed" value="<?php echo esc_attr($slider['animation_speed'] ?? 500); ?>" min="100" step="100" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Update Slider'); ?>
        </form>
        <a href="<?php echo admin_url('admin.php?page=simple-elegant-slider'); ?>" class="button">Back</a>
        <?php
    }

    public function page_init() {
        register_setting(
            'simple_elegant_slider_group',
            'simple_elegant_sliders',
            array($this, 'sanitize')
        );

        add_settings_section(
            'simple_elegant_slider_section',
            'Create New Slider',
            array($this, 'print_section_info'),
            'simple-elegant-slider'
        );

        add_settings_field(
            'slider_name',
            'Slider Name',
            array($this, 'slider_name_callback'),
            'simple-elegant-slider',
            'simple_elegant_slider_section'
        );

        add_settings_field(
            'slider_media',
            'Slider Media',
            array($this, 'slider_media_callback'),
            'simple-elegant-slider',
            'simple_elegant_slider_section'
        );

        add_settings_field(
            'auto_slide',
            'Auto Slide',
            array($this, 'auto_slide_callback'),
            'simple-elegant-slider',
            'simple_elegant_slider_section'
        );
    }

    public function sanitize($input) {
        if (isset($_POST['slider_edit_name'])) {
            // Updating existing slider
            $name = sanitize_text_field($_POST['slider_edit_name']);
            $this->sliders[$name] = array(
                'media' => explode(',', sanitize_text_field($_POST['slider_media'])),
                'auto_slide' => isset($_POST['auto_slide']) ? true : false,
                'show_captions' => isset($_POST['show_captions']) ? true : false,
                'caption_text_color' => sanitize_hex_color($_POST['caption_text_color'] ?? '#ffffff'),
                'animation_speed' => absint($_POST['animation_speed'] ?? 500)
            );
        } elseif (isset($input['slider_name']) && !empty($input['slider_name'])) {
            // Adding new slider
            $name = sanitize_text_field($input['slider_name']);
            $this->sliders[$name] = array(
                'media' => explode(',', sanitize_text_field($input['slider_media'])),
                'auto_slide' => isset($input['auto_slide']) ? true : false,
                'show_captions' => isset($input['show_captions']) ? true : false,
                'caption_text_color' => sanitize_hex_color($input['caption_text_color'] ?? '#ffffff'),
                'animation_speed' => absint($input['animation_speed'] ?? 500)
            );
        }

        return $this->sliders;
    }

    public function handle_slider_deletion() {
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['name'])) {
            $name = sanitize_text_field($_GET['name']);
            if (isset($this->sliders[$name])) {
                unset($this->sliders[$name]);
                update_option('simple_elegant_sliders', $this->sliders);
                wp_redirect(admin_url('admin.php?page=simple-elegant-slider'));
                exit;
            }
        }
    }

    public function print_section_info() {
        print 'Create a new slider below:';
    }

    public function slider_name_callback() {
        echo '<input type="text" id="slider_name" name="simple_elegant_sliders[slider_name]" value="" />';
    }

    public function slider_media_callback() {
        ?>
        <input type="hidden" id="slider_media" name="simple_elegant_sliders[slider_media]" value="" />
        <button type="button" class="button" id="select_slider_media">Select Media</button>
        <div id="slider_media_preview"></div>
        <?php
    }

    public function auto_slide_callback() {
        ?>
        <input type="checkbox" id="auto_slide" name="simple_elegant_sliders[auto_slide]" value="1" />
        <label for="auto_slide">Enable automatic sliding</label>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('swiper-css', 'https://unpkg.com/swiper/swiper-bundle.min.css');
        wp_enqueue_script('swiper-js', 'https://unpkg.com/swiper/swiper-bundle.min.js', array(), null, true);
        wp_enqueue_style('simple-elegant-slider-css', plugin_dir_url(__FILE__) . 'css/slider.css');
        wp_enqueue_script('simple-elegant-slider-js', plugin_dir_url(__FILE__) . 'js/slider.js', array('jquery', 'swiper-js'), null, true);
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_simple-elegant-slider' !== $hook) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('simple-elegant-slider-admin', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery', 'wp-color-picker'), null, true);
    }

    public function slider_shortcode($atts) {
        $atts = shortcode_atts(array(
            'name' => '',
        ), $atts);

        if (!isset($this->sliders[$atts['name']])) {
            return '';
        }

        $slider = $this->sliders[$atts['name']];
        $media_ids = $slider['media'];

        if (empty($media_ids)) {
            return '';
        }

        ob_start();
        ?>
        <div class="swiper simple-elegant-slider" data-auto-slide="<?php echo $slider['auto_slide'] ? 'true' : 'false'; ?>" data-animation-speed="<?php echo esc_attr($slider['animation_speed']); ?>">
            <div class="swiper-wrapper">
                <?php foreach ($media_ids as $media_id): ?>
                    <div class="swiper-slide">
                        <?php
                        $mime_type = get_post_mime_type($media_id);
                        if (strpos($mime_type, 'video') !== false) {
                            echo wp_video_shortcode(array('src' => wp_get_attachment_url($media_id)));
                        } else {
                            echo wp_get_attachment_image($media_id, 'full');
                        }
                        ?>
                        <?php if ($slider['show_captions'] && $caption = wp_get_attachment_caption($media_id)): ?>
                            <div class="swiper-caption" style="color: <?php echo esc_attr($slider['caption_text_color']); ?>;"><?php echo esc_html($caption); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new SimpleElegantSlider();