<?php
namespace BV\Shortcodes\Helpers;

use BV\API\MultimediaDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

class MultimediaToResource {
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Converte MultimediaDto para ResourceCardDto
     * 
     * Aplica todas as regras de negócio e formatação específicas de multimídias
     */
    public function convert(MultimediaDto $multimedia): ResourceCardDto {
        // 1. TÍTULO
        $title = '';
        if ($multimedia->title) {
            $titleText = strlen($multimedia->title) > 60 ? substr($multimedia->title, 0, 57) . '...' : $multimedia->title;
            if ($multimedia->url) {
                $title = '<a href="' . esc_url($multimedia->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = esc_html($titleText);
            }
        }
        
        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>
        
        <?php if ($multimedia->getFormattedSubjectArea()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Descritor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($multimedia->getFormattedSubjectArea(), 50)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($multimedia->getFormattedMediaType()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Tipo de Mídia:</span> 
                <span class="bvs-field-value"><?= esc_html($multimedia->getFormattedMediaType()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($multimedia->institution): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Instituição:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($multimedia->institution, 45)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($multimedia->format): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Formato:</span> 
                <span class="bvs-field-value"><?= esc_html($multimedia->format) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($multimedia->duration): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Duração:</span> 
                <span class="bvs-field-value"><?= esc_html($multimedia->duration) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($multimedia->created_date || $multimedia->updated_date): ?>
            <div class="bvs-dates">
                <?php if ($multimedia->getFormattedCreatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Criado:</span> 
                        <span class="bvs-date-value"><?= esc_html($multimedia->getFormattedCreatedDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($multimedia->getFormattedUpdatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Atualizado:</span> 
                        <span class="bvs-date-value"><?= esc_html($multimedia->getFormattedUpdatedDate()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $content = ob_get_clean();
        
        // 3. TAGS
        $tags = [];
        if ($multimedia->getFormattedSubjectArea()) {
            $tags[] = $multimedia->getFormattedSubjectArea();
        }
        if ($multimedia->getFormattedCountry()) {
            $tags[] = $multimedia->getFormattedCountry();
        }
        if ($multimedia->getFormattedMediaType()) {
            $tags[] = $multimedia->getFormattedMediaType();
        }
        
        // 4. LINK
        $link = $multimedia->url ?? '';
        
        // Cria o ResourceCardDto
        return new ResourceCardDto([
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'tags' => $tags,
        ]);
    }
}
