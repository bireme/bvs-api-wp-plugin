<?php
namespace BV\Shortcodes;

if (!defined('ABSPATH')) exit;

/**
 * Shortcode [bvs_bibliographic_databases] para exibir bases bibliográficas da BVS
 */
final class BvsBibliographicDatabasesShortcode {
    
    public function __construct() {
        add_shortcode('bvs_bibliographic_databases', [$this, 'render']);
        add_shortcode('bvs_databases', [$this, 'render']); // Alias curto
    }
    
    public function render($atts, $content = ''): string {
        $atts = shortcode_atts([
            'country' => '',
            'subject' => '',
            'term' => '',
            'coverage' => '',
            'searchTitle' => '',
            'count' => 12,
            'max' => 50,
            'template' => 'grid',
            'columns' => 3,
            'show_fields' => 'title,coverage,description,access',
        ], $atts, 'bvs_bibliographic_databases');
        
        // Mapear parâmetros da URL
        $urlParams = [
            'bvsCountry' => 'country',
            'bvsSubject' => 'subject',
            'bvsTerm' => 'term',
            'bvsCoverage' => 'coverage',
            'bvsTitle' => 'searchTitle',
        ];
        
        foreach ($urlParams as $urlKey => $attKey) {
            if (isset($_GET[$urlKey]) && !empty($_GET[$urlKey])) {
                $atts[$attKey] = sanitize_text_field($_GET[$urlKey]);
            }
        }
        
        // Sanitizar
        $atts['count'] = max(1, min(100, (int) $atts['count']));
        $atts['max'] = max(1, min(1000, (int) $atts['max']));
        $atts['columns'] = max(1, min(6, (int) $atts['columns']));
        
        $apiUrl = get_option('bv_bibliographic_databases_url');
        
        if (empty($apiUrl)) {
            return '<div class="bvs-error">⚠️ URL da API de Bases Bibliográficas não configurada. Acesse <a href="' . admin_url('admin.php?page=bvsalud-integrator-settings') . '">Configurações</a>.</div>';
        }
        
        return $this->renderPlaceholder($atts);
    }
    
    private function renderPlaceholder(array $atts): string {
        ob_start();
        ?>
        <div class="bvs-resources-container">
            <div class="bvs-placeholder" style="padding: 40px; text-align: center; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px;">
                <h3>📚 Bases Bibliográficas BVS</h3>
                <p>Shortcode configurado para exibir bases de dados bibliográficas da BVS.</p>
                <p><strong>URL configurada:</strong> <?php echo esc_html(get_option('bv_bibliographic_databases_url')); ?></p>
                <p><strong>Exemplos:</strong> LILACS, MEDLINE, SciELO, IBECS, etc.</p>
                <?php if (!empty($atts['country']) || !empty($atts['subject']) || !empty($atts['coverage']) || !empty($atts['term'])): ?>
                    <p><strong>Filtros ativos:</strong></p>
                    <ul style="list-style: none; padding: 0;">
                        <?php if (!empty($atts['country'])): ?>
                            <li>🌍 País: <?php echo esc_html($atts['country']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['subject'])): ?>
                            <li>📚 Assunto: <?php echo esc_html($atts['subject']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['coverage'])): ?>
                            <li>🗺️ Cobertura: <?php echo esc_html($atts['coverage']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['term'])): ?>
                            <li>🔍 Termo: <?php echo esc_html($atts['term']); ?></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <p style="margin-top: 20px;"><strong>Alias:</strong> Você também pode usar <code>[bvs_databases]</code></p>
                <p><em>Aguardando configuração da URL da API.</em></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

