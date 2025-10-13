<?php
namespace BV;

use BV\Admin\AdminMenu;
use BV\Admin\SettingsPage;
use BV\Shortcodes\BvsJournalsShortcode;
use BV\Shortcodes\BvsWebResourcesShortcode;
use BV\Shortcodes\BvsEventsShortcode;
use BV\Shortcodes\BvsMultimediaShortcode;
use BV\Shortcodes\BvsLegislationsShortcode;
use BV\Shortcodes\BvsBibliographicDatabasesShortcode;

final class Plugin {
    public function boot(): void {
        
        // Assets públicos
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);

        // Admin
        if (is_admin()) {
            (new AdminMenu())->register();
            (new SettingsPage())->register();
        }

        // Shortcodes Principais
        (new BvsJournalsShortcode())->register();
        (new BvsWebResourcesShortcode());
        
        // Shortcodes Adicionais (baseados nas URLs configuradas)
        new BvsEventsShortcode();
        new BvsMultimediaShortcode();
        new BvsLegislationsShortcode();
        new BvsBibliographicDatabasesShortcode();

        // Custom CSS/JS do admin (config) — só imprime no front se houver e usuário tiver salvo
        add_action('wp_head', [$this, 'printCustomCSS']);
        add_action('wp_footer', [$this, 'printCustomJS']);
    }

    public function enqueuePublicAssets(): void {
        wp_enqueue_style('bv-public', BV_PLUGIN_URL . 'src/Assets/public.css', [], BV_VERSION);
        wp_enqueue_script('bv-public', BV_PLUGIN_URL . 'src/Assets/public.js', ['wp-element'], BV_VERSION, true);
    }

    public function printCustomCSS(): void {
        $css = get_option('bv_custom_css');
        if (!empty($css)) {
            // Permite HTML cru apenas para quem tem unfiltered_html (admins); senão, limpa
            // Evista que um usuário crie algum css que interfira no site em locais onde não deveria (como aconteceu no portal)
            if (current_user_can('unfiltered_html')) {
                echo "<style id='bv-custom-css'>\n" . $css . "\n</style>";
            }
        }
    }

    public function printCustomJS(): void {
        $js = get_option('bv_custom_js');
        if (!empty($js)) {
            //Mesma coisa do css.
            //Evita uso indiscriminado e interferência no site
            if (current_user_can('unfiltered_html')) {
                echo "<script id='bv-custom-js'>\n" . $js . "\n</script>";
            }
        }
    }
}
