<?php
namespace BV\Admin;

if (!defined('ABSPATH'))
    exit;

final class SettingsPage
{
    const OPTION_CUSTOM_CSS = 'bv_custom_css';
    const OPTION_CUSTOM_JS = 'bv_custom_js';
    const OPTION_BVSALUD_TOKEN = 'bv_bvsalud_token';
    const OPTION_API_RESOURCES = 'bv_api_resources';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'bvsalud-integrator',
            __('Configurações', 'bvsalud-integrator'),
            __('Configurações', 'bvsalud-integrator'),
            'manage_options',
            'bvsalud-integrator-settings',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
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
        register_setting('bv_settings', self::OPTION_BVSALUD_TOKEN, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);

        register_setting('bv_settings', self::OPTION_API_RESOURCES, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeApiResources'],
            'default' => [],
        ]);

        add_settings_section('bv_main', __('Configurações da API', 'bvsalud-integrator'), function () {
            echo '<p>' . esc_html__('Configure a URL da API BVS Saúde e token de acesso.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator');

        add_settings_section('bv_resources', __('URLs de Recursos BVS', 'bvsalud-integrator'), function () {
            echo '<p>' . esc_html__('Configure as URLs dos diferentes recursos da BVS Saúde.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator');
        add_settings_field(self::OPTION_BVSALUD_TOKEN, __('Token BVS Saúde', 'bvsalud-integrator'), function () {
            $value = get_option(self::OPTION_BVSALUD_TOKEN, '');
            echo '<input type="password" name="' . esc_attr(self::OPTION_BVSALUD_TOKEN) . '" class="regular-text" placeholder="' . esc_attr__('Token de acesso', 'bvsalud-integrator') . '" value="' . esc_attr($value) . '"/>';
            echo '<p class="description">' . esc_html__('Token de autenticação para acesso à API BVS Saúde.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator', 'bv_main');

        // Configuração de Recursos BVS
        add_settings_field(self::OPTION_API_RESOURCES, __('Recursos BVS', 'bvsalud-integrator'), [$this, 'renderApiResourcesField'], 'bvsalud-integrator', 'bv_resources');

        // Seção de personalização
        add_settings_section('bv_customization', __('Personalização CSS/JS', 'bvsalud-integrator'), function () {
            echo '<p>' . esc_html__('Adicione CSS e JavaScript customizados para personalizar a exibição dos dados.', 'bvsalud-integrator') . '</p>';
        }, 'bvsalud-integrator');

        add_settings_field(self::OPTION_CUSTOM_CSS, __('CSS customizado', 'bvsalud-integrator'), function () {
            $value = esc_textarea(get_option(self::OPTION_CUSTOM_CSS, ''));
            echo '<textarea name="' . esc_attr(self::OPTION_CUSTOM_CSS) . '" rows="8" class="large-text code" placeholder="/* Seu CSS */">' . $value . '</textarea>';
        }, 'bvsalud-integrator', 'bv_customization');

        add_settings_field(self::OPTION_CUSTOM_JS, __('JS customizado', 'bvsalud-integrator'), function () {
            $value = esc_textarea(get_option(self::OPTION_CUSTOM_JS, ''));
            echo '<textarea name="' . esc_attr(self::OPTION_CUSTOM_JS) . '" rows="8" class="large-text code" placeholder="// Seu JS">' . $value . '</textarea>';
        }, 'bvsalud-integrator', 'bv_customization');
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options'))
            return;
        ?>
        <div class="wrap" style="max-width: 100%;">
            <h1><?php esc_html_e('Configurações BVSalud', 'bvsalud-integrator'); ?></h1>

            <div class="notice notice-info">
                <p><strong>Shortcode:</strong> <code>[bvs_resources type="TIPO"]</code> | <strong>Tipos:</strong> journals,
                    webResources, events, legislations, multimedia</p>
            </div>

            <form method="post" action="options.php" style="width: 100%;">
                <?php
                settings_fields('bv_settings');
                do_settings_sections('bvsalud-integrator');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueueAdminAssets($hook): void
    {
        if ($hook !== 'bvsalud-integrator_page_bvsalud-integrator-settings')
            return;
        wp_enqueue_style('bv-admin', \BV_PLUGIN_URL . 'src/Assets/admin.css', [], \BV_VERSION);
        wp_enqueue_script('bv-admin', \BV_PLUGIN_URL . 'src/Assets/admin.js', [], \BV_VERSION, true);
    }

    /**
     * Permite HTML cru apenas para quem tem unfiltered_html (admins); senão, limpa.
     */
    public function sanitizeMaybe($value): string
    {
        if (current_user_can('unfiltered_html'))
            return (string) $value;
        return sanitize_textarea_field((string) $value);
    }

    /**
     * Sanitiza o array de recursos da API
     */
    public function sanitizeApiResources($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $sanitized = [];
        foreach ($value as $resource) {
            if (isset($resource['resource']) && isset($resource['base_url'])) {
                $sanitized[] = [
                    'resource' => sanitize_text_field($resource['resource']),
                    'base_url' => esc_url_raw($resource['base_url'])
                ];
            }
        }

        return $sanitized;
    }

    /**
     * Renderiza o campo de configuração de recursos da API
     */
    public function renderApiResourcesField(): void
    {
        $resources = get_option(self::OPTION_API_RESOURCES, []);

        // Migrar dados antigos se necessário
        if (empty($resources)) {
            $resources = $this->migrateOldSettings();
        }

        echo '<div id="api-resources-container">';

        // Campos existentes
        foreach ($resources as $index => $resource) {
            $this->renderResourceRow($resource, $index);
        }

        // Template para novos recursos
        echo '<div id="resource-template" style="display: none;">';
        $this->renderResourceRow(['resource' => '', 'base_url' => ''], 'INDEX');
        echo '</div>';

        echo '<button type="button" id="add-resource" class="button button-secondary">' . esc_html__('+ Adicionar', 'bvsalud-integrator') . '</button>';
        echo '</div>';

        echo '<p class="description">' . esc_html__('Configure os tipos de recursos e suas URLs da API BVSalud.', 'bvsalud-integrator') . '</p>';
    }

    /**
     * Renderiza uma linha de recurso
     */
    private function renderResourceRow(array $resource, $index): void
    {
        $resourceValue = esc_attr($resource['resource'] ?? '');
        $urlValue = esc_url($resource['base_url'] ?? '');

        echo '<div class="resource-row" style="margin-bottom: 8px; display: flex; gap: 8px; align-items: center;">';
        echo '<input type="text" name="' . esc_attr(self::OPTION_API_RESOURCES) . '[' . $index . '][resource]" placeholder="' . esc_attr__('Tipo (ex: journals)', 'bvsalud-integrator') . '" value="' . $resourceValue . '" style="width: 150px;" />';
        echo '<input type="url" name="' . esc_attr(self::OPTION_API_RESOURCES) . '[' . $index . '][base_url]" placeholder="https://api.bvsalud.org/..." value="' . $urlValue . '" style="flex: 1; min-width: 400px;" />';
        echo '<button type="button" class="button remove-resource" style="color: #d63638;">' . esc_html__('×', 'bvsalud-integrator') . '</button>';
        echo '</div>';
    }

    /**
     * Migra configurações antigas para o novo formato
     */
    private function migrateOldSettings(): array
    {
        $resources = [];

        // Mapear recursos antigos para o novo formato
        $oldResources = [
            'lis' => get_option('bv_lis_url', ''),
            'events' => get_option('bv_events_url', ''),
            'multimedia' => get_option('bv_multimedia_url', ''),
            'legislations' => get_option('bv_legislations_url', ''),
            'bibliographic_databases' => get_option('bv_bibliographic_databases_url', ''),
        ];

        foreach ($oldResources as $resource => $url) {
            if (!empty($url)) {
                $resources[] = [
                    'resource' => $resource,
                    'base_url' => $url
                ];
            }
        }

        return $resources;
    }

    /**
     * Métodos auxiliares para acessar as configurações
     */
    public static function getBvsaludToken(): string
    {
        return get_option(self::OPTION_BVSALUD_TOKEN, '');
    }

    /**
     * Obtém todos os recursos configurados
     * 
     * @return array Array de recursos com resource e base_url
     */
    public static function getResourcesConfig(): array
    {
        $resources = get_option(self::OPTION_API_RESOURCES, []);

        // Se não há recursos configurados, tentar migrar dados antigos
        if (empty($resources)) {
            $instance = new self();
            $resources = $instance->migrateOldSettings();

            // Salvar os recursos migrados
            if (!empty($resources)) {
                update_option(self::OPTION_API_RESOURCES, $resources);
            }
        }

        return $resources;
    }

    /**
     * Busca a URL base para um recurso específico
     */
    public static function getResourceUrl(string $resource): string
    {
        $resources = self::getResourcesConfig();

        foreach ($resources as $res) {
            if (isset($res['resource']) && $res['resource'] === $resource) {
                return $res['base_url'] ?? '';
            }
        }

        return '';
    }

}
