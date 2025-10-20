<?php
namespace BV\Support;

if (!defined('ABSPATH')) exit;

/**
 * DTO genérico para renderização de cards de recursos
 * 
 * Este objeto abstrato representa qualquer tipo de recurso (journal, event, multimedia, etc)
 * de forma uniforme para renderização no grid.
 * 
 * Responsabilidades:
 * - Armazenar dados já formatados e prontos para exibição
 * - Não aplicar regras de negócio
 * - Não formatar dados (recebe tudo já formatado)
 */
final class ResourceCardDto {
    
    /**
     * @var string Título do card (pode conter HTML, ex: link)
     */
    public string $title;
    
    /**
     * @var string Conteúdo HTML do card (já formatado)
     */
    public string $content;
    
    /**
     * @var string URL do recurso
     */
    public string $link;
    
    /**
     * @var array Array de strings para as tags
     */
    public array $tags;
    
    /**
     * Construtor
     * 
     * @param array $data Array com os dados do card
     */
    public function __construct(array $data = []) {
        $this->title = $data['title'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->link = $data['link'] ?? '';
        $this->tags = $data['tags'] ?? [];
    }
    
    /**
     * Verifica se o card tem dados válidos
     */
    public function isValid(): bool {
        return !empty($this->title) || !empty($this->content);
    }
    
    /**
     * Retorna o HTML do título formatado
     */
    public function getTitleHtml(): string {
        return $this->title;
    }
    
    /**
     * Retorna o HTML do conteúdo formatado
     */
    public function getContentHtml(): string {
        return $this->content;
    }
    
    /**
     * Retorna o HTML do footer (botão de ação)
     */
    public function getFooterHtml(): string {
        if (empty($this->link)) {
            return '';
        }
        
        return '<a href="' . esc_url($this->link) . '" target="_blank" rel="noopener" class="bvs-btn">Acessar</a>';
    }
    
    /**
     * Retorna o HTML das tags formatadas
     */
    public function getTagsHtml(): string {
        if (empty($this->tags)) {
            return '';
        }
        
        $html = '<div class="bvs-tags">';
        
        foreach ($this->tags as $index => $tag) {
            $tagClass = $index === 0 ? 'bvs-tag bvs-tag-primary' : 'bvs-tag bvs-tag-secondary';
            $html .= '<span class="' . $tagClass . '">' . esc_html($tag) . '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Converte para array
     */
    public function toArray(): array {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'link' => $this->link,
            'tags' => $this->tags,
        ];
    }
}

