<?php
namespace BV\Support;

if (!defined('ABSPATH')) exit;

final class Helpers {
    /**
     * Carrega um template do plugin permitindo override pelo tema:
     *  - theme/bvsalud-integrator/template-name.php
     *  - plugin/src/Templates/template-name.php (fallback)
     *
     * @param string $templateName
     * @param array  $vars
     */
    public static function renderTemplate(string $templateName, array $vars = []): string {
        $themePath = locate_template('bvsalud-integrator/' . $templateName);
        $pluginPath = \BV_PLUGIN_DIR . 'src/Templates/' . $templateName;

        $file = $themePath ?: $pluginPath;
        if (!file_exists($file)) return '';

        ob_start();
        // Torna $vars disponíveis como variáveis
        extract($vars, EXTR_SKIP);
        include $file;
        return (string) ob_get_clean();
    }
}
