<?php
/**
 * Plugin Name:       BVSalud Integrator
 * Description:       Consome API externa BVSALUD, e cria shortcodes para exibir dados de diferentes fontes.
 * Version:           1.0.0
 * Author:            Jefferson Augusto Lopes
 * Text Domain:       bvsalud-integrator
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

define('BV_PLUGIN_FILE', __FILE__);
define('BV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BV_VERSION', '1.0.0');

require_once BV_PLUGIN_DIR . 'src/Autoloader.php';
BV\Autoloader::init('BV', BV_PLUGIN_DIR . 'src');

add_action('plugins_loaded', function () {
    load_plugin_textdomain('bvsalud-integrator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    (new BV\Plugin())->boot();
});
