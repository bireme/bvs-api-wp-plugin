<?php
namespace BV\Shortcodes\Helpers;

use BV\API\CenterDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

class CenterToResource {
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Converte InstitutionDto para ResourceCardDto
     * 
     * Aplica todas as regras de negócio e formatação específicas de Institutionos
     */
    public function convert(CenterDto $center): ResourceCardDto {

        if ($center->unit) {
            $titleText = strlen($center->unit) > 60 ? substr($center->unit, 0, 57) . '...' : $center->unit;
            if ($center->url) {
                $title = 'un<a href="' . esc_url($center->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = 'un'. esc_html($titleText);
            }
        }

        // Nametitle = $center->title;
        if ($center->title) {
            $titleText = strlen($center->title) > 60 ? substr($center->title, 0, 57) . '...' : $center->title;
            if ($center->url) {
                $title .= '<BR><a href="' . esc_url($center->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title .= '<BR>' . esc_html($titleText);
            }
        }
        
        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>
        

        <?php if ($center->cooperative_center_code): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Código do centro:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($center->cooperative_center_code, 50)) ?></span>
            </div>
        <?php endif; ?>

        <?php
        $addressParts = [];

        if (!empty($center->address)) {
            $addressParts[] = $this->truncateText($center->address, 50);
        }

        if (!empty($center->city)) {
            $addressParts[] = $this->truncateText($center->city, 50);
        }

        if (!empty($center->state)) {
            $addressParts[] = $this->truncateText($center->state, 50);
        }

        if (!empty($center->country)) {
            $addressParts[] = $this->truncateText($center->getFormattedCountry(), 50);
        }

        $fullAddress = implode(' - ', $addressParts);
        ?>

        <?php if (!empty($fullAddress)) : ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Endereço:</span>
                <span class="bvs-field-value"><?= esc_html($fullAddress); ?></span>
            </div>
        <?php endif; ?>


        <?php if (!empty($center->contact)) : ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Contatos:</span>
                <span class="bvs-field-value"><?= $this->truncateText(esc_html($center->getContact()),50); ?></span>
            </div>
        <?php endif; ?>

        
        
        <?php
        $content = ob_get_clean();
        
        // 3. TAGS
        $tags = [];
        /*
        if ($center->getFormattedSubjectArea()) {
            $tags[] = $center->getFormattedSubjectArea();
        }
        if ($center->getFormattedCountry()) {
            $tags[] = $center->getFormattedCountry();
        }
            *
        if ($center->getFormattedTypes()) {
            foreach()
            $tags[] = $center->getFormattedTypes();

        }
            */

         if (!empty($center->institution_type)) {
            foreach($center->institution_type as $types){
            $tags[] = $center->getFormattedType($types);
            }

        }
        

        // 4. LINK
        //$link = $center->url ?? '';
        $link = 'https://www.google.com.br';
        // Cria o ResourceCardDto
        return new ResourceCardDto([
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'tags' => $tags,
            'colunas' => 2,
        ]);
    }
}
