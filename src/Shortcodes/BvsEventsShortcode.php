<?php
namespace BV\Shortcodes;

use BV\API\BvsaludClient;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

/**
 * Shortcode [bvs_events] para exibir eventos da BVS
 */
final class BvsEventsShortcode {
    
    public function __construct() {
        add_shortcode('bvs_events', [$this, 'render']);
    }
    
    public function render($atts, $content = ''): string {
        $atts = shortcode_atts([
            'country' => '',
            'subject' => '',
            'term' => '',
            'searchTitle' => '',
            'count' => 12,
            'max' => 50,
            'template' => 'grid',
            'columns' => 3,
            'show_fields' => 'title,date,location,type',
        ], $atts, 'bvs_events');
        
        // Mapear parâmetros da URL
        $urlParams = [
            'bvsCountry' => 'country',
            'bvsSubject' => 'subject',
            'bvsTerm' => 'term',
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
        
        $apiUrl = get_option('bv_events_url');
        
        if (empty($apiUrl)) {
            return '<div class="bvs-error">⚠️ URL da API de Eventos não configurada. Acesse <a href="' . admin_url('admin.php?page=bvsalud-integrator-settings') . '">Configurações</a>.</div>';
        }
        
        try {
            // TODO: Implementar cliente específico para eventos
            // Por enquanto, retorna mensagem
            return $this->renderPlaceholder($atts);
            
        } catch (\Exception $e) {
            return '<div class="bvs-error">Erro ao buscar eventos: ' . esc_html($e->getMessage()) . '</div>';
        }
    }
    
    private function renderPlaceholder(array $atts): string {
        ob_start();
        ?>
        <div class="bvs-resources-container">
            <div class="bvs-placeholder" style="padding: 40px; text-align: center; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px;">
                <h3>📅 Eventos BVS</h3>
                <p>Shortcode configurado para exibir eventos da BVS Saúde.</p>
                <p><strong>URL configurada:</strong> <?php echo esc_html(get_option('bv_events_url')); ?></p>
                <p><strong>Filtros ativos:</strong></p>
                <ul style="list-style: none; padding: 0;">
                    <?php if (!empty($atts['country'])): ?>
                        <li>🌍 País: <?php echo esc_html($atts['country']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($atts['subject'])): ?>
                        <li>📚 Assunto: <?php echo esc_html($atts['subject']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($atts['term'])): ?>
                        <li>🔍 Termo: <?php echo esc_html($atts['term']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($atts['searchTitle'])): ?>
                        <li>📝 Título: <?php echo esc_html($atts['searchTitle']); ?></li>
                    <?php endif; ?>
                </ul>
                <p style="margin-top: 20px;"><em>Implementação da API de eventos em desenvolvimento.</em></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

