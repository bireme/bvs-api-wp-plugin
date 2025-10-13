<?php
namespace BV\Admin;

if (!defined('ABSPATH')) exit;

final class SettingsPage {
    const OPTION_CUSTOM_CSS = 'bv_custom_css';
    const OPTION_CUSTOM_JS  = 'bv_custom_js';
    const OPTION_JOURNALS_API_URL = 'bv_journals_api_url';
    const OPTION_BVSALUD_TOKEN = 'bv_bvsalud_token';
    const OPTION_LIS_URL = 'bv_lis_url';
    const OPTION_EVENTS_URL = 'bv_events_url';
    const OPTION_MULTIMEDIA_URL = 'bv_multimedia_url';
    const OPTION_LEGISLATIONS_URL = 'bv_legislations_url';
    const OPTION_BIBLIOGRAPHIC_DATABASES_URL = 'bv_bibliographic_databases_url';

    public function register(): void {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function addMenu(): void {
        add_submenu_page(
            'bvsalud-integrator',
            __('Configurações', 'bvsalud-integrator'),
            __('Configurações', 'bvsalud-integrator'),
            'manage_options',
            'bvsalud-integrator-settings',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void {
        register_setting('bv_settings', self::OPTION_CUSTOM_CSS, [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitizeMaybe'],
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_CUSTOM_JS, [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitizeMaybe'],
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_JOURNALS_API_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_BVSALUD_TOKEN, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_LIS_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_EVENTS_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_MULTIMEDIA_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_LEGISLATIONS_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_BIBLIOGRAPHIC_DATABASES_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        add_settings_section('bv_main', __('Configurações da API', 'bvsalud-integrator'), function () {
            echo '<p>' . esc_html__('Configure a URL da API BVS Saúde e token de acesso.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator');

        add_settings_section('bv_resources', __('URLs de Recursos BVS', 'bvsalud-integrator'), function () {
            echo '<p>' . esc_html__('Configure as URLs dos diferentes recursos da BVS Saúde.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator');

        add_settings_field(self::OPTION_JOURNALS_API_URL, __('URL API BVS Saúde', 'bvsalud-integrator'), function () {
            $value = esc_url(get_option(self::OPTION_JOURNALS_API_URL, ''));
            echo '<input type="url" name="' . esc_attr(self::OPTION_JOURNALS_API_URL) . '" class="regular-text" placeholder="https://api.bvsalud.org/endpoint" value="' . $value . '"/>';
            echo '<p class="description">' . esc_html__('URL da API BVS Saúde para consulta de journals.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator', 'bv_main');

        add_settings_field(self::OPTION_BVSALUD_TOKEN, __('Token BVS Saúde', 'bvsalud-integrator'), function () {
            $value = get_option(self::OPTION_BVSALUD_TOKEN, '');
            echo '<input type="password" name="' . esc_attr(self::OPTION_BVSALUD_TOKEN) . '" class="regular-text" placeholder="' . esc_attr__('Token de acesso', 'bvsalud-integrator') . '" value="' . esc_attr($value) . '"/>';
            echo '<p class="description">' . esc_html__('Token de autenticação para acesso à API BVS Saúde.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator', 'bv_main');

        // URLs de Recursos BVS
        add_settings_field(self::OPTION_LIS_URL, __('URL LIS', 'bvsalud-integrator'), function () {
            $value = esc_url(get_option(self::OPTION_LIS_URL, ''));
            echo '<input type="url" name="' . esc_attr(self::OPTION_LIS_URL) . '" class="regular-text" placeholder="https://" value="' . $value . '"/>';
            echo '<p class="description">' . esc_html__('URL do recurso LIS (Library and Information Science).', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator', 'bv_resources');

        add_settings_field(self::OPTION_EVENTS_URL, __('URL Events', 'bvsalud-integrator'), function () {
            $value = esc_url(get_option(self::OPTION_EVENTS_URL, ''));
            echo '<input type="url" name="' . esc_attr(self::OPTION_EVENTS_URL) . '" class="regular-text" placeholder="https://" value="' . $value . '"/>';
            echo '<p class="description">' . esc_html__('URL do recurso Events (Eventos).', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator', 'bv_resources');

        add_settings_field(self::OPTION_MULTIMEDIA_URL, __('URL Multimedia', 'bvsalud-integrator'), function () {
            $value = esc_url(get_option(self::OPTION_MULTIMEDIA_URL, ''));
            echo '<input type="url" name="' . esc_attr(self::OPTION_MULTIMEDIA_URL) . '" class="regular-text" placeholder="https://" value="' . $value . '"/>';
            echo '<p class="description">' . esc_html__('URL do recurso Multimedia (Multimídia).', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator', 'bv_resources');

        add_settings_field(self::OPTION_LEGISLATIONS_URL, __('URL Legislations', 'bvsalud-integrator'), function () {
            $value = esc_url(get_option(self::OPTION_LEGISLATIONS_URL, ''));
            echo '<input type="url" name="' . esc_attr(self::OPTION_LEGISLATIONS_URL) . '" class="regular-text" placeholder="https://" value="' . $value . '"/>';
            echo '<p class="description">' . esc_html__('URL do recurso Legislations (Legislação).', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator', 'bv_resources');

        add_settings_field(self::OPTION_BIBLIOGRAPHIC_DATABASES_URL, __('URL Bibliographic Databases', 'bvsalud-integrator'), function () {
            $value = esc_url(get_option(self::OPTION_BIBLIOGRAPHIC_DATABASES_URL, ''));
            echo '<input type="url" name="' . esc_attr(self::OPTION_BIBLIOGRAPHIC_DATABASES_URL) . '" class="regular-text" placeholder="https://" value="' . $value . '"/>';
            echo '<p class="description">' . esc_html__('URL do recurso Bibliographic Databases (Bases de Dados Bibliográficas).', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator', 'bv_resources');

        // Seção de personalização
        add_settings_section('bv_customization', __('Personalização CSS/JS', 'bvsalud-integrator'), function () {
            echo '<p>' . esc_html__('Adicione CSS e JavaScript customizados para personalizar a exibição dos dados.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator');

        add_settings_field(self::OPTION_CUSTOM_CSS, __('CSS customizado', 'bvsalud-integrator'), function () {
            $value = esc_textarea(get_option(self::OPTION_CUSTOM_CSS, ''));
            echo '<textarea name="' . esc_attr(self::OPTION_CUSTOM_CSS) . '" rows="8" class="large-text code" placeholder="/* Seu CSS */">'.$value.'</textarea>';
        }, 'bvsalud-integrator', 'bv_customization');

        add_settings_field(self::OPTION_CUSTOM_JS, __('JS customizado', 'bvsalud-integrator'), function () {
            $value = esc_textarea(get_option(self::OPTION_CUSTOM_JS, ''));
            echo '<textarea name="' . esc_attr(self::OPTION_CUSTOM_JS) . '" rows="8" class="large-text code" placeholder="// Seu JS">'.$value.'</textarea>';
        }, 'bvsalud-integrator', 'bv_customization');
    }

    public function renderPage(): void {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('BVSalud Integrator - Configurações', 'bvsalud-integrator'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('bv_settings');
                do_settings_sections('bvsalud-integrator');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueueAdminAssets($hook): void {
        if ($hook !== 'bvsalud-integrator_page_bvsalud-integrator-settings') return;
        wp_enqueue_style('bv-admin', \BV_PLUGIN_URL . 'src/Assets/admin.css', [], \BV_VERSION);
        wp_enqueue_script('bv-admin', \BV_PLUGIN_URL . 'src/Assets/admin.js', [], \BV_VERSION, true);
    }

    /**
     * Permite HTML cru apenas para quem tem unfiltered_html (admins); senão, limpa.
     */
    public function sanitizeMaybe($value): string {
        if (current_user_can('unfiltered_html')) return (string) $value;
        return sanitize_textarea_field((string) $value);
    }

    /**
     * Métodos auxiliares para acessar as configurações
     */
    public static function getJournalsApiUrl(): string {
        return get_option(self::OPTION_JOURNALS_API_URL, '');
    }

    public static function getBvsaludToken(): string {
        return get_option(self::OPTION_BVSALUD_TOKEN, '');
    }

    public static function getLisUrl(): string {
        return get_option(self::OPTION_LIS_URL, '');
    }

    public static function getEventsUrl(): string {
        return get_option(self::OPTION_EVENTS_URL, '');
    }

    public static function getMultimediaUrl(): string {
        return get_option(self::OPTION_MULTIMEDIA_URL, '');
    }

    public static function getLegislationsUrl(): string {
        return get_option(self::OPTION_LEGISLATIONS_URL, '');
    }

    public static function getBibliographicDatabasesUrl(): string {
        return get_option(self::OPTION_BIBLIOGRAPHIC_DATABASES_URL, '');
    }

}
