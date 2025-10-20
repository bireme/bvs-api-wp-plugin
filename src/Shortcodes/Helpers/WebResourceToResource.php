<?php
namespace BV\Shortcodes\Helpers;

use BV\API\WebResourceDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

class WebResourceToResource {
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Converte WebResourceDto para ResourceCardDto
     * 
     * Aplica todas as regras de negócio e formatação específicas de recursos web
     */
    public function convert(WebResourceDto $webResource): ResourceCardDto {
        // 1. TÍTULO
        $title = '';
        if ($webResource->title) {
            $titleText = strlen($webResource->title) > 60 ? substr($webResource->title, 0, 57) . '...' : $webResource->title;
            if ($webResource->url) {
                $title = '<a href="' . esc_url($webResource->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = esc_html($titleText);
            }
        }
        
        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>
        
        <?php if ($webResource->getFormattedSubjectArea()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Descritor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($webResource->getFormattedSubjectArea(), 50)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($webResource->institution): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Instituição:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($webResource->institution, 45)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($webResource->publisher): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Editor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($webResource->publisher, 40)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($webResource->getFormattedType()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Tipo:</span> 
                <span class="bvs-field-value"><?= esc_html($webResource->getFormattedType()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($webResource->created_date || $webResource->updated_date): ?>
            <div class="bvs-dates">
                <?php if ($webResource->getFormattedCreatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Criado:</span> 
                        <span class="bvs-date-value"><?= esc_html($webResource->getFormattedCreatedDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($webResource->getFormattedUpdatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Atualizado:</span> 
                        <span class="bvs-date-value"><?= esc_html($webResource->getFormattedUpdatedDate()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $content = ob_get_clean();
        
        // 3. TAGS
        $tags = [];
        if ($webResource->getFormattedSubjectArea()) {
            $tags[] = $webResource->getFormattedSubjectArea();
        }
        if ($webResource->getFormattedCountry()) {
            $tags[] = $webResource->getFormattedCountry();
        }
        
        // 4. LINK
        $link = $webResource->url ?? '';
        
        // Cria o ResourceCardDto
        return new ResourceCardDto([
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'tags' => $tags,
        ]);
    }
}
