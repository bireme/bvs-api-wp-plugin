<?php
/**
 * Template Grid Genérico para Recursos BVS
 * 
 * Este template é reutilizável para qualquer tipo de recurso (journals, events, multimedia, etc)
 * 
 * Responsabilidades:
 * - Renderizar a estrutura do grid
 * - Iterar sobre ResourceCardDto[]
 * - Passar dados para o componente resource-card
 * 
 * NÃO faz:
 * - Formatação de dados
 * - Regras de negócio
 * - Conhecimento de tipos específicos de recursos
 * 
 * @var array $resources Array de ResourceCardDto
 * @var array $atts Atributos do shortcode
 * @var int $total Total de recursos
 */

use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;
?>

<div class="bvs-resources-container" data-template="grid">
    
    <?php if ($total > 0): ?>
        <?php
        $currentPage = $atts['page'] ?? 1;
        $perPage = $atts['limit'] ?? 12;
        $startItem = (($currentPage - 1) * $perPage) + 1;
        $endItem = min($currentPage * $perPage, $total);
        ?>
        <div class="bvs-resources-header">
            <p class="bvs-resources-count">
                Mostrando <?= $startItem ?>-<?= $endItem ?> de <?= $total ?> items
            </p>
        </div>
    <?php endif; ?>
    
    <div class="bvs-grid" data-columns="<?= esc_attr($atts['columns'] ?? 4) ?>">
        <?php foreach ($resources as $resource): ?>
            <?php 
            /** @var ResourceCardDto $resource */
            if (!$resource->isValid()) continue;
            
            // Prepara dados para o componente
            $cardTitle = $resource->getTitleHtml();
            
            // Monta o conteúdo completo (fields + tags)
            $cardContent = $resource->getContentHtml();
            
            // Adiciona tags ao conteúdo se existirem
            if (!empty($resource->tags)) {
                $cardContent .= $resource->getTagsHtml();
            }
            
            $cardFooter = $resource->getFooterHtml();
            
            // Inclui o componente
            include __DIR__ . '/components/resource-card.php';
            ?>
            
        <?php endforeach; ?>
    </div>
    
    <?php if (isset($atts['show_pagination']) && $atts['show_pagination'] && $total > ($atts['limit'] ?? 12)): ?>
        <div class="bvs-pagination">
            <?php
            $currentPage = $atts['page'] ?? 1;
            $perPage = $atts['limit'] ?? 12;
            $totalPages = ceil($total / $perPage);
            
            if ($totalPages > 1):
                // Link para primeira página
                if ($currentPage > 1): 
                    $firstUrl = add_query_arg('bvsPage', 1);
                    ?>
                    <a href="<?= esc_url($firstUrl) ?>" class="page-link">« Primeira</a>
                <?php endif;
                
                // Calcula quais páginas mostrar em torno da página atual
                if ($totalPages <= 7):
                    $start = 1;
                    $end = $totalPages;
                    $showStartDots = false;
                    $showEndDots = false;
                else:
                    // Mostra páginas em torno da atual
                    $range = 2; // 2 páginas antes e depois da atual
                    $start = max(1, $currentPage - $range);
                    $end = min($totalPages, $currentPage + $range);
                    
                    // Ajusta se estiver muito próximo do início ou fim
                    if ($start <= 2):
                        $start = 1;
                        $end = min(5, $totalPages); // Mostra até 5 páginas do início
                    endif;
                    if ($end >= $totalPages - 1):
                        $end = $totalPages;
                        $start = max(1, $totalPages - 4); // Mostra até 5 páginas do fim
                    endif;
                    
                    $showStartDots = $start > 2;
                    $showEndDots = $end < $totalPages - 1;
                endif;
                
                // Mostra ... no início se necessário
                if ($showStartDots): ?>
                    <span class="page-dots">...</span>
                <?php endif;
                
                // Links de páginas
                for ($i = $start; $i <= $end; $i++):
                    $class = $i === $currentPage ? 'page-link current' : 'page-link';
                    
                    if ($i === $currentPage): ?>
                        <span class="<?= $class ?>"><?= $i ?></span>
                    <?php else:
                        $pageUrl = add_query_arg('bvsPage', $i);
                        ?>
                        <a href="<?= esc_url($pageUrl) ?>" class="<?= $class ?>"><?= $i ?></a>
                    <?php endif;
                endfor;
                
                // Mostra ... no fim se necessário
                if ($showEndDots): ?>
                    <span class="page-dots">...</span>
                <?php endif;
                
                // Mostra última página se não estiver no range
                if ($end < $totalPages):
                    if ($currentPage === $totalPages): ?>
                        <span class="page-link current"><?= $totalPages ?></span>
                    <?php else:
                        $lastUrl = add_query_arg('bvsPage', $totalPages);
                        ?>
                        <a href="<?= esc_url($lastUrl) ?>" class="page-link"><?= $totalPages ?></a>
                    <?php endif;
                endif;
            endif;
            ?>
        </div>
    <?php endif; ?>
    
</div>

