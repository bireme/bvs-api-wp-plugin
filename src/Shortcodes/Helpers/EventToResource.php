<?php
namespace BV\Shortcodes\Helpers;

use BV\API\EventDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

class EventToResource {
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Converte EventDto para ResourceCardDto
     * 
     * Aplica todas as regras de negócio e formatação específicas de eventos
     */
    public function convert(EventDto $event): ResourceCardDto {
        // Nametitle = $event->title;
        if ($event->title) {
            $titleText = strlen($event->title) > 60 ? substr($event->title, 0, 57) . '...' : $event->title;
            if ($event->url) {
                $title = '<a href="' . esc_url($event->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = esc_html($titleText);
            }
        }
        
        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>
        
        <?php if ($event->getFormattedSubjectArea()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Descritor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($event->getFormattedSubjectArea(), 50)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($event->getFormattedEventType()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Tipo de Evento:</span> 
                <span class="bvs-field-value"><?= esc_html($event->getFormattedEventType()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($event->institution): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Instituição:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($event->institution, 45)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($event->location): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Local:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($event->location, 40)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($event->start_date || $event->end_date): ?>
            <div class="bvs-dates">
                <?php if ($event->getFormattedStartDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Início:</span> 
                        <span class="bvs-date-value"><?= esc_html($event->getFormattedStartDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($event->getFormattedEndDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Fim:</span> 
                        <span class="bvs-date-value"><?= esc_html($event->getFormattedEndDate()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $content = ob_get_clean();
        
        // 3. TAGS
        $tags = [];
        if ($event->getFormattedSubjectArea()) {
            $tags[] = $event->getFormattedSubjectArea();
        }
        if ($event->getFormattedCountry()) {
            $tags[] = $event->getFormattedCountry();
        }
        if ($event->getFormattedEventType()) {
            $tags[] = $event->getFormattedEventType();
        }
        
        // 4. LINK
        $link = $event->url ?? '';
        
        // Cria o ResourceCardDto
        return new ResourceCardDto([
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'tags' => $tags,
        ]);
    }
}
