<?php
namespace BV\API;

if (!defined('ABSPATH')) exit;

/**
 * DTO para normalizar dados de recursos web da API BVS Saúde
 */
final class WebResourceDto {
    public ?string $id;
    public ?string $title;
    public ?string $url;
    public ?string $description;
    public ?string $type;
    public ?string $country;
    public ?array $languages;
    public ?string $status;
    public ?string $subject_area;
    public ?string $publisher;
    public ?string $institution;
    public ?array $collections;
    public ?string $created_date;
    public ?string $updated_date;
    public ?string $access_date;
    public ?string $coverage;
    public ?string $format;
    public ?string $language_interface;
    public ?string $update_frequency;
    public ?string $keywords;
    public ?string $abstract;
    public ?string $contact;
    public ?string $copyright;
    public ?string $license;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? $data['resource_title'] ?? $this->extractFirstValue($data['shortened_title'] ?? null);
        
        // Normalizar campos que podem vir como arrays da API BVS
        $this->url = $this->extractFirstValue($data['url'] ?? $data['resource_url'] ?? $data['link'] ?? null);
        $this->description = $this->extractFirstValue($data['description'] ?? $data['abstract'] ?? $data['summary'] ?? null);
        $this->type = $this->extractFirstValue($data['type'] ?? $data['resource_type'] ?? $data['document_type'] ?? null);
        $this->country = $this->extractFirstValue($data['country'] ?? null);
        $this->status = $this->extractFirstValue($data['status'] ?? null);
        $this->publisher = $this->extractFirstValue($data['publisher'] ?? $data['responsibility_mention'] ?? null);
        $this->institution = $this->extractFirstValue($data['institution'] ?? $data['institution_name'] ?? null);

        $subjectArea = $data['subject_area'] ?? $data['subjectArea'] ?? $data['descriptor'] ?? $data['mh'] ?? null;
        $this->subject_area = $this->extractFirstValue($subjectArea);
        
        $this->created_date = $this->extractFirstValue($data['created_date'] ?? $data['createdDate'] ?? null);
        $this->updated_date = $this->extractFirstValue($data['updated_date'] ?? $data['updatedDate'] ?? null);
        $this->access_date = $this->extractFirstValue($data['access_date'] ?? $data['accessDate'] ?? null);
        $this->coverage = $this->extractFirstValue($data['coverage'] ?? null);
        $this->format = $this->extractFirstValue($data['format'] ?? null);
        $this->language_interface = $this->extractFirstValue($data['language_interface'] ?? $data['interface_language'] ?? null);
        $this->update_frequency = $this->extractFirstValue($data['update_frequency'] ?? $data['frequency'] ?? null);
        $this->keywords = $this->extractFirstValue($data['keywords'] ?? $data['subject'] ?? null);
        $this->abstract = $this->extractFirstValue($data['abstract'] ?? $data['summary'] ?? null);
        $this->contact = $this->extractFirstValue($data['contact'] ?? $data['contact_info'] ?? null);
        $this->copyright = $this->extractFirstValue($data['copyright'] ?? null);
        $this->license = $this->extractFirstValue($data['license'] ?? null);

        $this->languages = $this->normalizeToArray($data['languages'] ?? $data['language'] ?? []);
        $this->collections = $this->normalizeToArray($data['collections'] ?? []);
    }

    /**
     * Converte o DTO para array
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'type' => $this->type,
            'country' => $this->country,
            'languages' => $this->languages,
            'status' => $this->status,
            'subject_area' => $this->subject_area,
            'publisher' => $this->publisher,
            'institution' => $this->institution,
            'collections' => $this->collections,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
            'access_date' => $this->access_date,
            'coverage' => $this->coverage,
            'format' => $this->format,
            'language_interface' => $this->language_interface,
            'update_frequency' => $this->update_frequency,
            'keywords' => $this->keywords,
            'abstract' => $this->abstract,
            'contact' => $this->contact,
            'copyright' => $this->copyright,
            'license' => $this->license,
        ];
    }

    /**
     * Retorna apenas os campos principais para exibição
     */
    public function getDisplayData(): array {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'type' => $this->type,
            'country' => $this->country,
            'languages' => $this->languages,
            'publisher' => $this->publisher,
            'institution' => $this->institution,
        ];
    }

    /**
     * Verifica se o DTO é válido
     */
    public function isValid(): bool {
        return !empty($this->title) || !empty($this->id);
    }

    /**
     * Retorna as linguagens como string separada por vírgula
     */
    public function getLanguagesString(): string {
        if (!is_array($this->languages) || empty($this->languages)) {
            return '';
        }
        return implode(', ', $this->languages);
    }

    /**
     * Retorna o tipo de recurso formatado
     */
    public function getFormattedType(): string {
        if (!$this->type) {
            return 'Recurso Web';
        }
        
        $typeMap = [
            'database' => 'Base de Dados',
            'directory' => 'Diretório',
            'portal' => 'Portal',
            'website' => 'Site',
            'repository' => 'Repositório',
            'catalog' => 'Catálogo',
            'library' => 'Biblioteca Digital',
            'journal' => 'Periódico',
            'newsletter' => 'Newsletter',
            'blog' => 'Blog',
            'forum' => 'Fórum',
            'wiki' => 'Wiki',
            'tool' => 'Ferramenta',
            'service' => 'Serviço',
        ];

        return $typeMap[strtolower($this->type)] ?? ucfirst($this->type);
    }

    /**
     * Extrai o primeiro valor se for array, ou retorna o valor se for string/null
     */
    private function extractFirstValue($value): ?string {
        if (is_array($value)) {
            return !empty($value) ? (string)$value[0] : null;
        }
        return $value ? (string)$value : null;
    }

    /**
     * Normaliza valor para array
     */
    private function normalizeToArray($value): array {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && !empty($value)) {
            return [$value];
        }
        return [];
    }
}
