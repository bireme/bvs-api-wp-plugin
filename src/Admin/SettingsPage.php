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
                ?>

                <div class="bv-config-layout">
                    <div class="bv-config-main">
                        <h2><?php esc_html_e('Configurações da API', 'bvsalud-integrator'); ?></h2>
                        <p><?php esc_html_e('Configure a URL da API BVS Saúde e token de acesso.', 'bvsalud-integrator'); ?></p>

                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label
                                            for="<?php echo esc_attr(self::OPTION_BVSALUD_TOKEN); ?>"><?php esc_html_e('Token BVS Saúde', 'bvsalud-integrator'); ?></label>
                                    </th>
                                    <td>
                                        <?php
                                        $value = get_option(self::OPTION_BVSALUD_TOKEN, '');
                                        ?>
                                        <input type="password" name="<?php echo esc_attr(self::OPTION_BVSALUD_TOKEN); ?>"
                                            id="<?php echo esc_attr(self::OPTION_BVSALUD_TOKEN); ?>" class="regular-text"
                                            placeholder="<?php esc_attr_e('Token de acesso', 'bvsalud-integrator'); ?>"
                                            value="<?php echo esc_attr($value); ?>" />
                                        <p class="description">
                                            <?php esc_html_e('Token de autenticação para acesso à API BVS Saúde.', 'bvsalud-integrator'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <h2><?php esc_html_e('URLs de Recursos BVS', 'bvsalud-integrator'); ?></h2>
                        <p><?php esc_html_e('Configure as URLs dos diferentes recursos da BVS Saúde.', 'bvsalud-integrator'); ?>
                        </p>

                        <?php $this->renderApiResourcesField(); ?>
                    </div>

                    <?php
                    // Renderizar modal apenas uma vez
                    $this->renderFiltersModal();
                    ?>

                    <div class="bv-config-sidebar">
                        <h2><?php esc_html_e('Personalização CSS/JS', 'bvsalud-integrator'); ?></h2>
                        <p><?php esc_html_e('Adicione CSS e JavaScript customizados para personalizar a exibição dos dados.', 'bvsalud-integrator'); ?>
                        </p>

                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label
                                            for="<?php echo esc_attr(self::OPTION_CUSTOM_CSS); ?>"><?php esc_html_e('CSS customizado', 'bvsalud-integrator'); ?></label>
                                    </th>
                                    <td>
                                        <?php
                                        $cssValue = esc_textarea(get_option(self::OPTION_CUSTOM_CSS, ''));
                                        ?>
                                        <textarea name="<?php echo esc_attr(self::OPTION_CUSTOM_CSS); ?>"
                                            id="<?php echo esc_attr(self::OPTION_CUSTOM_CSS); ?>" rows="12"
                                            class="large-text code"
                                            placeholder="/* Seu CSS */"><?php echo $cssValue; ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label
                                            for="<?php echo esc_attr(self::OPTION_CUSTOM_JS); ?>"><?php esc_html_e('JS customizado', 'bvsalud-integrator'); ?></label>
                                    </th>
                                    <td>
                                        <?php
                                        $jsValue = esc_textarea(get_option(self::OPTION_CUSTOM_JS, ''));
                                        ?>
                                        <textarea name="<?php echo esc_attr(self::OPTION_CUSTOM_JS); ?>"
                                            id="<?php echo esc_attr(self::OPTION_CUSTOM_JS); ?>" rows="12"
                                            class="large-text code" placeholder="// Seu JS"><?php echo $jsValue; ?></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php submit_button(); ?>
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
                $sanitizedResource = [
                    'resource' => sanitize_text_field($resource['resource']),
                    'base_url' => esc_url_raw($resource['base_url'])
                ];

                // Sanitizar filtros se existirem
                if (isset($resource['filter_types']) && is_array($resource['filter_types'])) {
                    $sanitizedResource['filter_types'] = [];
                    foreach ($resource['filter_types'] as $filter) {
                        if (
                            isset($filter['key']) && isset($filter['label']) &&
                            !empty(trim($filter['key'])) && !empty(trim($filter['label']))
                        ) {
                            $sanitizedResource['filter_types'][] = [
                                'key' => sanitize_text_field(trim($filter['key'])),
                                'label' => sanitize_text_field(trim($filter['label']))
                            ];
                        }
                    }
                }

                $sanitized[] = $sanitizedResource;
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

        echo '<div class="resources-table-wrapper">';
        echo '<div id="api-resources-container">';
        echo '<table class="resources-table">';

        // Cabeçalho da tabela
        echo '<thead>';
        echo '<tr>';
        echo '<th style="width: 15%;">' . esc_html__('Tipo', 'bvsalud-integrator') . '</th>';
        echo '<th style="width: 55%;">' . esc_html__('URL', 'bvsalud-integrator') . '</th>';
        echo '<th style="width: 15%;">' . esc_html__('Filtros', 'bvsalud-integrator') . '</th>';
        echo '<th style="width: 15%;">' . esc_html__('Ações', 'bvsalud-integrator') . '</th>';
        echo '</tr>';
        echo '</thead>';

        // Corpo da tabela
        echo '<tbody>';

        // Campos existentes
        foreach ($resources as $index => $resource) {
            echo '<tr class="resource-row">';
            $this->renderResourceRow($resource, $index);
            echo '</tr>';
        }

        // Template para novos recursos
        echo '<tr id="resource-template" style="display: none;" class="resource-row">';
        $this->renderResourceRow(['resource' => '', 'base_url' => '', 'filter_types' => []], 'INDEX');
        echo '</tr>';

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';

        echo '<button type="button" id="add-resource" class="button button-secondary">' . esc_html__('+ Adicionar Recurso', 'bvsalud-integrator') . '</button>';
    }

    /**
     * Renderiza o modal para gerenciar filtros
     */
    private function renderFiltersModal(): void
    {
        ?>
        <div id="filters-modal" class="filters-modal" style="display: none;">
            <div class="filters-modal-overlay"></div>
            <div class="filters-modal-content">
                <div class="filters-modal-header">
                    <h2><?php esc_html_e('Gerenciar Filtros', 'bvsalud-integrator'); ?></h2>
                    <button type="button" class="filters-modal-close">&times;</button>
                </div>
                <div class="filters-modal-body">
                    <div id="filters-container" class="filters-list-dragable"></div>
                    <div style="margin-top: 16px;">
                        <button type="button" id="add-filter-btn" class="button button-secondary">
                            <?php esc_html_e('+ Adicionar Filtro', 'bvsalud-integrator'); ?>
                        </button>
                    </div>
                </div>
                <div class="filters-modal-footer">
                    <button type="button" id="save-filters-btn" class="button button-primary">
                        <?php esc_html_e('Salvar', 'bvsalud-integrator'); ?>
                    </button>
                    <button type="button"
                        class="filters-modal-close button"><?php esc_html_e('Cancelar', 'bvsalud-integrator'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza uma linha de recurso (células da tabela)
     */
    private function renderResourceRow(array $resource, $index): void
    {
        $resourceValue = esc_attr($resource['resource'] ?? '');
        $urlValue = esc_url($resource['base_url'] ?? '');
        $filters = $resource['filter_types'] ?? [];
        $filterCount = count($filters);
        ?>
        <td>
            <input type="text"
                name="<?php echo esc_attr(self::OPTION_API_RESOURCES); ?>[<?php echo esc_attr($index); ?>][resource]"
                placeholder="<?php esc_attr_e('ex: journals', 'bvsalud-integrator'); ?>"
                value="<?php echo $resourceValue; ?>" />
        </td>
        <td>
            <input type="url"
                name="<?php echo esc_attr(self::OPTION_API_RESOURCES); ?>[<?php echo esc_attr($index); ?>][base_url]"
                placeholder="https://api.bvsalud.org/..." value="<?php echo $urlValue; ?>" />
        </td>
        <td>
            <div class="filters-compact">
                <span class="filter-count"><?php echo esc_html($filterCount); ?></span>
                <button type="button" class="button button-secondary manage-filters-btn"
                    data-index="<?php echo esc_attr($index); ?>"><?php esc_html_e('Gerenciar', 'bvsalud-integrator'); ?></button>

                <?php if (!empty($filters)): ?>
                    <div class="filters-display" data-index="<?php echo esc_attr($index); ?>" style="display: none;">
                        <?php foreach ($filters as $filterIndex => $filter): ?>
                            <div class="filter-item">
                                <input type="hidden"
                                    name="<?php echo esc_attr(self::OPTION_API_RESOURCES); ?>[<?php echo esc_attr($index); ?>][filter_types][<?php echo esc_attr($filterIndex); ?>][key]"
                                    value="<?php echo esc_attr($filter['key'] ?? ''); ?>" />
                                <input type="hidden"
                                    name="<?php echo esc_attr(self::OPTION_API_RESOURCES); ?>[<?php echo esc_attr($index); ?>][filter_types][<?php echo esc_attr($filterIndex); ?>][label]"
                                    value="<?php echo esc_attr($filter['label'] ?? ''); ?>" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <script type="application/json"
                        class="filter-data-<?php echo esc_attr($index); ?>"><?php echo wp_json_encode($filters); ?></script>
                <?php endif; ?>
            </div>
        </td>
        <td>
            <button type="button" class="button remove-resource"><?php esc_html_e('Remover', 'bvsalud-integrator'); ?></button>
        </td>
        <?php
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
