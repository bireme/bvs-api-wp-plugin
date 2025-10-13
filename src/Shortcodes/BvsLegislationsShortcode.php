<?php
namespace BV\Shortcodes;

if (!defined('ABSPATH')) exit;

/**
 * Shortcode [bvs_legislations] para exibir legislações da BVS
 */
final class BvsLegislationsShortcode {
    
    public function __construct() {
        add_shortcode('bvs_legislations', [$this, 'render']);
    }
    
    public function render($atts, $content = ''): string {
        $atts = shortcode_atts([
            'country' => '',
            'subject' => '',
            'term' => '',
            'year' => '',
            'searchTitle' => '',
            'count' => 12,
            'max' => 50,
            'template' => 'default',
            'show_fields' => 'title,country,year,type',
        ], $atts, 'bvs_legislations');
        
        // Mapear parâmetros da URL
        $urlParams = [
            'bvsCountry' => 'country',
            'bvsSubject' => 'subject',
            'bvsTerm' => 'term',
            'bvsYear' => 'year',
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
        
        $apiUrl = get_option('bv_legislations_url');
        
        if (empty($apiUrl)) {
            return '<div class="bvs-error">⚠️ URL da API de Legislações não configurada. Acesse <a href="' . admin_url('admin.php?page=bvsalud-integrator-settings') . '">Configurações</a>.</div>';
        }
        
        return $this->renderPlaceholder($atts);
    }
    
    private function renderPlaceholder(array $atts): string {
        ob_start();
        ?>
        <div class="bvs-resources-container">
            <div class="bvs-placeholder" style="padding: 40px; text-align: center; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px;">
                <h3>⚖️ Legislações em Saúde BVS</h3>
                <p>Shortcode configurado para exibir legislações e normas em saúde da BVS.</p>
                <p><strong>URL configurada:</strong> <?php echo esc_html(get_option('bv_legislations_url')); ?></p>
                <p><strong>Tipos:</strong> Leis, Decretos, Portarias, Resoluções, Normas Técnicas</p>
                <?php if (!empty($atts['country']) || !empty($atts['subject']) || !empty($atts['year']) || !empty($atts['term'])): ?>
                    <p><strong>Filtros ativos:</strong></p>
                    <ul style="list-style: none; padding: 0;">
                        <?php if (!empty($atts['country'])): ?>
                            <li>🌍 País: <?php echo esc_html($atts['country']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['subject'])): ?>
                            <li>📚 Assunto: <?php echo esc_html($atts['subject']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['year'])): ?>
                            <li>📅 Ano: <?php echo esc_html($atts['year']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['term'])): ?>
                            <li>🔍 Termo: <?php echo esc_html($atts['term']); ?></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <p style="margin-top: 20px;"><em>Aguardando configuração da URL da API.</em></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

