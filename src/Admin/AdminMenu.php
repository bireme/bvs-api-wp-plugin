<?php
namespace BV\Admin;

if (!defined('ABSPATH')) exit;

final class AdminMenu {
    const MENU_SLUG = 'bvsalud-integrator';
    const CAPABILITY = 'manage_options';

    public function register(): void {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu(): void {
       
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

    public function renderMainPage(): void {
        if (!current_user_can(self::CAPABILITY)) return;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('BVSalud Integrator', 'bvsalud-integrator'); ?></h1>
            <div class="card">
                <h2><?php esc_html_e('Bem-vindo ao BVSalud Integrator', 'bvsalud-integrator'); ?></h2>
                <p><?php esc_html_e('Este plugin permite consumir dados de periódicos científicos via API BVS Saúde, exibindo os dados através de shortcodes flexíveis.', 'bvsalud-integrator'); ?></p>
                
                
                
                <h3><?php esc_html_e('Páginas de Configuração:', 'bvsalud-integrator'); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e('Configurações', 'bvsalud-integrator'); ?></strong> - <?php esc_html_e('Configure URL da API BVS Saúde, token de acesso e CSS/JS customizados', 'bvsalud-integrator'); ?></li>
                </ul>

                <h3><?php esc_html_e('Shortcodes Disponíveis:', 'bvsalud-integrator'); ?></h3>
                
                <h4><?php esc_html_e('1. Journals (BVS Saúde):', 'bvsalud-integrator'); ?></h4>
                <ul>
                    <li><code>[bvs_journals country="Brasil"]</code> - <?php esc_html_e('Journals por país', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_journals showFilters="true" template="grid" columns="3"]</code> - <?php esc_html_e('Com sidebar de filtros interativos', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_journals search="medicina" template="grid"]</code> - <?php esc_html_e('Busca com template grid responsivo', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_journals issn="1234-5678"]</code> - <?php esc_html_e('Journal específico por ISSN', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_journals subject="Medicina" country="Brasil"]</code> - <?php esc_html_e('Filtros combinados (AND)', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_journals searchTitle="saúde pública"]</code> - <?php esc_html_e('Busca por título', 'bvsalud-integrator'); ?></li>
                </ul>
                
                <h4><?php esc_html_e('2. Recursos Web (LIS):', 'bvsalud-integrator'); ?></h4>
                <ul>
                    <li><code>[bvs_web_resources country="Brasil"]</code> - <?php esc_html_e('Recursos por país', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_web_resources showFilters="true" template="grid"]</code> - <?php esc_html_e('Com sidebar de filtros', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_web_resources type="database" subject="Medicina"]</code> - <?php esc_html_e('Bases de dados por assunto', 'bvsalud-integrator'); ?></li>
                </ul>
                
                <h4><?php esc_html_e('3. Eventos:', 'bvsalud-integrator'); ?></h4>
                <ul>
                    <li><code>[bvs_events country="Brasil"]</code> - <?php esc_html_e('Eventos por país', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_events term="covid" template="grid"]</code> - <?php esc_html_e('Eventos por termo', 'bvsalud-integrator'); ?></li>
                </ul>
                
                <h4><?php esc_html_e('4. Multimídia:', 'bvsalud-integrator'); ?></h4>
                <ul>
                    <li><code>[bvs_multimedia type="video" subject="Medicina"]</code> - <?php esc_html_e('Vídeos por assunto', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_multimedia term="saúde" template="grid"]</code> - <?php esc_html_e('Recursos multimídia', 'bvsalud-integrator'); ?></li>
                </ul>
                
                <h4><?php esc_html_e('5. Legislações:', 'bvsalud-integrator'); ?></h4>
                <ul>
                    <li><code>[bvs_legislations country="Brasil" year="2024"]</code> - <?php esc_html_e('Legislações por país e ano', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_legislations term="vigilância sanitária"]</code> - <?php esc_html_e('Legislações por termo', 'bvsalud-integrator'); ?></li>
                </ul>
                
                <h4><?php esc_html_e('6. Bases Bibliográficas:', 'bvsalud-integrator'); ?></h4>
                <ul>
                    <li><code>[bvs_databases coverage="América Latina"]</code> - <?php esc_html_e('Bases por cobertura', 'bvsalud-integrator'); ?></li>
                    <li><code>[bvs_bibliographic_databases subject="Enfermagem"]</code> - <?php esc_html_e('Bases por assunto', 'bvsalud-integrator'); ?></li>
                </ul>
                
                <h3><?php esc_html_e('Funcionalidades:', 'bvsalud-integrator'); ?></h3>
                <ul>
                    <li>🔍 <strong><?php esc_html_e('Filtros Múltiplos', 'bvsalud-integrator'); ?></strong> - <?php esc_html_e('País, assunto, título, tipo, ISSN', 'bvsalud-integrator'); ?></li>
                    <li>🎛️ <strong><?php esc_html_e('Sidebar de Filtros', 'bvsalud-integrator'); ?></strong> - <?php esc_html_e('Interface visual com checkboxes para múltiplos países', 'bvsalud-integrator'); ?></li>
                    <li>🔗 <strong><?php esc_html_e('Filtros AND', 'bvsalud-integrator'); ?></strong> - <?php esc_html_e('Todos os filtros funcionam em conjunto', 'bvsalud-integrator'); ?></li>
                    <li>🌍 <strong><?php esc_html_e('Múltiplos Países', 'bvsalud-integrator'); ?></strong> - <?php esc_html_e('Seleção de vários países via checkboxes (OR)', 'bvsalud-integrator'); ?></li>
                    <li>📱 <strong><?php esc_html_e('Responsivo', 'bvsalud-integrator'); ?></strong> - <?php esc_html_e('Layout adaptável para desktop e mobile', 'bvsalud-integrator'); ?></li>
                    <li>🔌 <strong><?php esc_html_e('Parâmetros URL', 'bvsalud-integrator'); ?></strong> - <?php esc_html_e('Todos os filtros aceitam parâmetros via query string', 'bvsalud-integrator'); ?></li>
                </ul>

                
                <h3><?php esc_html_e('Exemplos de URLs com Filtros:', 'bvsalud-integrator'); ?></h3>
                <ul>
                    <li><code>?bvsCountry=Brasil&bvsTemplate=grid</code> - <?php esc_html_e('Journals do Brasil em grid', 'bvsalud-integrator'); ?></li>
                    <li><code>?bvsTitle=medicina&bvsCountry=Brasil</code> - <?php esc_html_e('Busca por título + país', 'bvsalud-integrator'); ?></li>
                    <li><code>?bvsCountries[]=Brasil&bvsCountries[]=Argentina</code> - <?php esc_html_e('Múltiplos países', 'bvsalud-integrator'); ?></li>
                    <li><code>?bvsSubject=Medicina&bvsCountry=Brasil</code> - <?php esc_html_e('Por assunto e país (AND)', 'bvsalud-integrator'); ?></li>
                </ul>
                
                <p style="margin-top: 20px;">
                    <strong><?php esc_html_e('📚 Documentação Completa:', 'bvsalud-integrator'); ?></strong><br>
                    <?php esc_html_e('Para mais exemplos e documentação detalhada, consulte os arquivos README.md e WEB_RESOURCES_USAGE.md no diretório do plugin.', 'bvsalud-integrator'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
