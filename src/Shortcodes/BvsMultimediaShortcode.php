<?php
namespace BV\Shortcodes;

if (!defined('ABSPATH')) exit;

/**
 * Shortcode [bvs_multimedia] para exibir recursos multimídia da BVS
 */
final class BvsMultimediaShortcode {
    
    public function __construct() {
        add_shortcode('bvs_multimedia', [$this, 'render']);
    }
    
    public function render($atts, $content = ''): string {
        $atts = shortcode_atts([
            'country' => '',
            'subject' => '',
            'term' => '',
            'type' => '',
            'searchTitle' => '',
            'count' => 12,
            'max' => 50,
            'template' => 'grid',
            'columns' => 4,
            'show_fields' => 'title,type,duration,format',
        ], $atts, 'bvs_multimedia');
        
        // Mapear parâmetros da URL
        $urlParams = [
            'bvsCountry' => 'country',
            'bvsSubject' => 'subject',
            'bvsTerm' => 'term',
            'bvsType' => 'type',
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
        
        $apiUrl = get_option('bv_multimedia_url');
        
        if (empty($apiUrl)) {
            return '<div class="bvs-error">⚠️ URL da API de Multimídia não configurada. Acesse <a href="' . admin_url('admin.php?page=bvsalud-integrator-settings') . '">Configurações</a>.</div>';
        }
        
        return $this->renderPlaceholder($atts);
    }
    
    private function renderPlaceholder(array $atts): string {
        ob_start();
        ?>
        <div class="bvs-resources-container">
            <div class="bvs-placeholder" style="padding: 40px; text-align: center; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px;">
                <h3>🎬 Recursos Multimídia BVS</h3>
                <p>Shortcode configurado para exibir vídeos, áudios e imagens da BVS Saúde.</p>
                <p><strong>URL configurada:</strong> <?php echo esc_html(get_option('bv_multimedia_url')); ?></p>
                <p><strong>Tipos suportados:</strong> Vídeos, Áudios, Imagens, Apresentações</p>
                <?php if (!empty($atts['country']) || !empty($atts['subject']) || !empty($atts['term']) || !empty($atts['type'])): ?>
                    <p><strong>Filtros ativos:</strong></p>
                    <ul style="list-style: none; padding: 0;">
                        <?php if (!empty($atts['country'])): ?>
                            <li>🌍 País: <?php echo esc_html($atts['country']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['subject'])): ?>
                            <li>📚 Assunto: <?php echo esc_html($atts['subject']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['type'])): ?>
                            <li>🎥 Tipo: <?php echo esc_html($atts['type']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($atts['term'])): ?>
                            <li>🔍 Termo: <?php echo esc_html($atts['term']); ?></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <p style="margin-top: 20px;"><em>Implementação da API de multimídia em desenvolvimento.</em></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

