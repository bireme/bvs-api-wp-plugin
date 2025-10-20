<?php
/**
 * Resource Card Component
 * 
 * Componente genérico e reutilizável para exibir cards de recursos
 * 
 * @param string $cardTitle   - HTML do título do card
 * @param string $cardContent - HTML do conteúdo do card
 * @param string $cardFooter  - HTML do footer (ações) do card
 * @param string $cardUrl     - URL para link (opcional)
 */

if (!defined('ABSPATH')) exit;

// Valores padrão
$cardTitle = $cardTitle ?? '';
$cardContent = $cardContent ?? '';
$cardFooter = $cardFooter ?? '';
$cardUrl = $cardUrl ?? '';
?>

<div class="bvs-item">
    <div class="bvs-item-content">
        
        <?php if (!empty($cardTitle)): ?>
            <h4 class="bvs-item-title">
                <?= $cardTitle ?>
            </h4>
        <?php endif; ?>
        
        <?php if (!empty($cardContent)): ?>
            <div class="bvs-item-info">
                <?= $cardContent ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($cardFooter)): ?>
            <div class="bvs-item-actions">
                <?= $cardFooter ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

