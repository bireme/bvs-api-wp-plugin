<?php
namespace BV\Shortcodes\Helpers;

use BV\API\LegislationDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

class LegislationToResource {
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Converte LegislationDto para ResourceCardDto
     * 
     * Aplica todas as regras de negócio e formatação específicas de legislações
     */
    public function convert(LegislationDto $legislation): ResourceCardDto {
        // 1. TÍTULO
        $title = '';
        if ($legislation->title) {
            $titleText = strlen($legislation->title) > 60 ? substr($legislation->title, 0, 57) . '...' : $legislation->title;
            if ($legislation->url) {
                $title = '<a href="' . esc_url($legislation->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = esc_html($titleText);
            }
        }
        
        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>
        
        <?php if ($legislation->getFormattedSubjectArea()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Descritor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($legislation->getFormattedSubjectArea(), 50)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($legislation->getFormattedLegislationType()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Tipo de Legislação:</span> 
                <span class="bvs-field-value"><?= esc_html($legislation->getFormattedLegislationType()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($legislation->institution): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Instituição:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($legislation->institution, 45)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($legislation->legislation_number): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Número:</span> 
                <span class="bvs-field-value"><?= esc_html($legislation->legislation_number) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($legislation->publication_date || $legislation->created_date): ?>
            <div class="bvs-dates">
                <?php if ($legislation->getFormattedPublicationDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Publicação:</span> 
                        <span class="bvs-date-value"><?= esc_html($legislation->getFormattedPublicationDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($legislation->getFormattedCreatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Criado:</span> 
                        <span class="bvs-date-value"><?= esc_html($legislation->getFormattedCreatedDate()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $content = ob_get_clean();
        
        // 3. TAGS
        $tags = [];
        if ($legislation->getFormattedSubjectArea()) {
            $tags[] = $legislation->getFormattedSubjectArea();
        }
        if ($legislation->getFormattedCountry()) {
            $tags[] = $legislation->getFormattedCountry();
        }
        if ($legislation->getFormattedLegislationType()) {
            $tags[] = $legislation->getFormattedLegislationType();
        }
        
        // 4. LINK
        $link = $legislation->url ?? '';
        
        // Cria o ResourceCardDto
        return new ResourceCardDto([
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'tags' => $tags,
        ]);
    }
}
