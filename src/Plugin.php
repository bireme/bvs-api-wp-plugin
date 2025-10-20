<?php
namespace BV;

use BV\Admin\AdminMenu;
use BV\Admin\SettingsPage;
use BV\Shortcodes\BvsResourcesShortcode;

final class Plugin
{
    public function boot(): void
    {

        // Assets públicos
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);

        // Admin
        if (is_admin()) {
            (new AdminMenu())->register();
            (new SettingsPage())->register();
        }

        // Shortcode Genérico de Recursos (substitui todos os shortcodes antigos)
        (new BvsResourcesShortcode())->register();

        // Custom CSS/JS do admin (config) — só imprime no front se houver e usuário tiver salvo
        add_action('wp_head', [$this, 'printCustomCSS']);
        add_action('wp_footer', [$this, 'printCustomJS']);
    }

    public function enqueuePublicAssets(): void
    {
        wp_enqueue_style('bv-public', BV_PLUGIN_URL . 'src/Assets/public.css', [], BV_VERSION);
        wp_enqueue_script('bv-public', BV_PLUGIN_URL . 'src/Assets/public.js', ['wp-element'], BV_VERSION, true);
    }

    public function printCustomCSS(): void
    {
        $css = get_option('bv_custom_css');
        if (!empty($css)) {
            // Permite HTML apenas para administradores
            if (current_user_can('unfiltered_html')) {
                echo "<style id='bv-custom-css'>\n" . $css . "\n</style>";
            }
        }
    }

    public function printCustomJS(): void
    {
        $js = get_option('bv_custom_js');
        if (!empty($js)) {
            // Permite JavaScript apenas para administradores
            if (current_user_can('unfiltered_html')) {
                echo "<script id='bv-custom-js'>\n" . $js . "\n</script>";
            }
        }
    }
}
