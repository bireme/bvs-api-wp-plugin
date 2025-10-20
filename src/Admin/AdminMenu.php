<?php
namespace BV\Admin;

if (!defined('ABSPATH'))
    exit;

final class AdminMenu
{
    const MENU_SLUG = 'bvsalud-integrator';
    const CAPABILITY = 'manage_options';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu(): void
    {

        add_menu_page(
            __('BVSalud Integrator', 'bvsalud-integrator'),
            __('BVSalud Integrator', 'bvsalud-integrator'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderMainPage'],
            'dashicons-book-alt',
            30
        );


        add_submenu_page(
            self::MENU_SLUG,
            __('Sobre', 'bvsalud-integrator'),
            __('Sobre', 'bvsalud-integrator'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderMainPage']
        );
    }

    public function renderMainPage(): void
    {
        if (!current_user_can(self::CAPABILITY))
            return;
        ?>
        <div class="wrap" style="max-width: 100%;">
            <h1><?php esc_html_e('BVSalud Integrator', 'bvsalud-integrator'); ?></h1>

            <!-- Introdução -->
            <div class="card" style="margin-bottom: 20px;">
                <h2><?php esc_html_e('Bem-vindo ao BVSalud Integrator', 'bvsalud-integrator'); ?></h2>
                <p><?php esc_html_e('Plugin WordPress para integração com a API BVS Saúde, permitindo exibir recursos através de shortcode genérico personalizável.', 'bvsalud-integrator'); ?>
                </p>
            </div>

            <!-- Layout em 3 colunas -->
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">

                <!-- Shortcode Genérico -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3><?php esc_html_e('Shortcode Genérico', 'bvsalud-integrator'); ?></h3>
                    <p><strong><?php esc_html_e('Sintaxe:', 'bvsalud-integrator'); ?></strong></p>
                    <code
                        style="display: block; background: #f1f1f1; padding: 10px; margin: 10px 0;">[bvs_resources type="TIPO" parâmetros...]</code>

                    <h4><?php esc_html_e('Tipos de Recursos:', 'bvsalud-integrator'); ?></h4>
                    <ul>
                        <li><code>journals</code> - <?php esc_html_e('Periódicos científicos', 'bvsalud-integrator'); ?></li>
                        <li><code>webResources</code> - <?php esc_html_e('Recursos web', 'bvsalud-integrator'); ?></li>
                        <li><code>events</code> - <?php esc_html_e('Eventos em saúde', 'bvsalud-integrator'); ?></li>
                        <li><code>legislations</code> - <?php esc_html_e('Leis, decretos', 'bvsalud-integrator'); ?></li>
                        <li><code>multimedia</code> - <?php esc_html_e('Vídeos, áudios', 'bvsalud-integrator'); ?></li>
                    </ul>
                </div>

                <!-- Exemplos de Uso -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3><?php esc_html_e('Exemplos de Uso', 'bvsalud-integrator'); ?></h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 8px;"><code>[bvs_resources type="journals" country="Brazil" limit="10"]</code>
                        </li>
                        <li style="margin-bottom: 8px;"><code>[bvs_resources type="webResources" show_filters="true"]</code>
                        </li>
                        <li style="margin-bottom: 8px;"><code>[bvs_resources type="events" show_pagination="true"]</code></li>
                        <li style="margin-bottom: 8px;"><code>[bvs_resources type="legislations" subject="Saúde"]</code></li>
                        <li style="margin-bottom: 8px;"><code>[bvs_resources type="multimedia" search="covid"]</code></li>
                    </ul>
                </div>

                <!-- Funcionalidades -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3><?php esc_html_e('Funcionalidades', 'bvsalud-integrator'); ?></h3>
                    <ul>
                        <li>🔄 <strong><?php esc_html_e('Shortcode Genérico', 'bvsalud-integrator'); ?></strong></li>
                        <li>🔍 <strong><?php esc_html_e('Filtros Múltiplos', 'bvsalud-integrator'); ?></strong></li>
                        <li>🎛️ <strong><?php esc_html_e('Sidebar de Filtros', 'bvsalud-integrator'); ?></strong></li>
                        <li>📱 <strong><?php esc_html_e('Layout Responsivo', 'bvsalud-integrator'); ?></strong></li>
                        <li>📄 <strong><?php esc_html_e('Paginação', 'bvsalud-integrator'); ?></strong></li>
                        <li>🔌 <strong><?php esc_html_e('Parâmetros URL', 'bvsalud-integrator'); ?></strong></li>
                    </ul>
                </div>
            </div>

            <!-- Segunda linha com 2 colunas -->
            <div style="display: flex; gap: 20px; margin-top: 20px;">

                <!-- Parâmetros via URL -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3><?php esc_html_e('Parâmetros via URL', 'bvsalud-integrator'); ?></h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 8px;"><code>?bvsType=journals&bvsCountry=Brazil</code></li>
                        <li style="margin-bottom: 8px;"><code>?bvsSearch=medicina&bvsLimit=20</code></li>
                        <li style="margin-bottom: 8px;"><code>?bvsCountries[]=Brazil&bvsCountries[]=Argentina</code></li>
                        <li style="margin-bottom: 8px;"><code>?bvsSubject=Medicina&bvsPage=2</code></li>
                    </ul>
                </div>

                <!-- Configuração -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3><?php esc_html_e('Configuração', 'bvsalud-integrator'); ?></h3>
                    <p><?php esc_html_e('Configure os recursos da API BVS Saúde com suas respectivas URLs e token de acesso.', 'bvsalud-integrator'); ?>
                    </p>
                    <p><strong><?php esc_html_e('Acesse:', 'bvsalud-integrator'); ?></strong> <a
                            href="<?php echo admin_url('admin.php?page=bvsalud-integrator-settings'); ?>"><?php esc_html_e('BVSalud > Configurações', 'bvsalud-integrator'); ?></a>
                    </p>
                </div>
            </div>

            <!-- Documentação -->
            <div class="card" style="margin-top: 20px;">
                <h3><?php esc_html_e('📚 Documentação', 'bvsalud-integrator'); ?></h3>
                <p><?php esc_html_e('Para mais exemplos e documentação detalhada, consulte o arquivo README.md no diretório do plugin.', 'bvsalud-integrator'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
