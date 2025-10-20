<?php
namespace BV\Shortcodes\Helpers;

use BV\API\JournalDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

class JournalToResource {
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    /**
     * Converte JournalDto para ResourceCardDto
     * 
     * Aplica todas as regras de negócio e formatação específicas de journals
     */
    public  function convert(JournalDto $journal): ResourceCardDto {
        // 1. TÍTULO
        $title = '';
        if ($journal->title) {
            $titleText = strlen($journal->title) > 60 ? substr($journal->title, 0, 57) . '...' : $journal->title;
            if ($journal->url) {
                $title = '<a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = esc_html($titleText);
            }
        }
        
        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>
        
        <?php if ($journal->getFormattedSubjectArea()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Descritor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($journal->getFormattedSubjectArea(), 50)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($journal->responsibility_mention): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Responsabilidade:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($journal->responsibility_mention, 45)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($journal->publisher): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Editor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($journal->publisher, 40)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($journal->getPrimaryIssn()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">ISSN:</span> 
                <span class="bvs-field-value"><?= esc_html($journal->getPrimaryIssn()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($journal->initial_date || $journal->created_date || $journal->updated_date): ?>
            <div class="bvs-dates">
                <?php if ($journal->getFormattedInitialDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Início:</span> 
                        <span class="bvs-date-value"><?= esc_html($journal->getFormattedInitialDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($journal->getFormattedCreatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Criado:</span> 
                        <span class="bvs-date-value"><?= esc_html($journal->getFormattedCreatedDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($journal->getFormattedUpdatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Atualizado:</span> 
                        <span class="bvs-date-value"><?= esc_html($journal->getFormattedUpdatedDate()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $content = ob_get_clean();
        
        // 3. TAGS
        $tags = [];
        if ($journal->getFormattedSubjectArea()) {
            $tags[] = $journal->getFormattedSubjectArea();
        }
        if ($journal->getFormattedCountry()) {
            $tags[] = $journal->getFormattedCountry();
        }
        
        // 4. LINK
        $link = $journal->url ?? '';
        
        // Cria o ResourceCardDto
        return new ResourceCardDto([
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'tags' => $tags,
        ]);
    }
}