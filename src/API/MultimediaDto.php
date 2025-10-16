<?php
namespace BV\API;

if (!defined('ABSPATH'))
    exit;

/**
 * DTO para normalizar dados de recursos multimídia da API BVS Saúde
 */
final class MultimediaDto
{
    public ?string $id;
    public ?string $title;
    public ?string $url;
    public ?string $description;
    public ?string $media_type;
    public ?string $media_collection;
    public ?string $country;
    public ?array $languages;
    public ?string $status;
    public ?array $subject_area;
    public ?array $thematic_area;
    public ?array $descriptor;
    public ?array $mh;
    public ?array $author;
    public ?string $created_date;
    public ?string $updated_date;
    public ?string $publication_date;
    public ?string $publication_year;
    public ?array $publication_country;
    public ?array $language_display;
    public ?array $media_type_display;
    public ?array $thematic_area_display;
    public ?string $django_ct;
    public ?string $django_id;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? $data['django_id'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->django_ct = $data['django_ct'] ?? null;
        $this->django_id = $data['django_id'] ?? null;

        // URL - pode vir como array de links
        $this->url = $this->extractFirstValue($data['link'] ?? null);

        // Description
        $this->description = $this->extractFirstValue($data['description'] ?? null);

        // Media type
        $this->media_type = $this->extractFirstValue($data['media_type'] ?? null);

        // Media collection
        $this->media_collection = $this->extractFirstValue($data['media_collection'] ?? null);

        // País - pode vir como array
        $country = $data['publication_country'] ?? $data['country'] ?? null;
        if (is_array($country) && !empty($country)) {
            $this->country = $country[0];
            $this->publication_country = $country;
        } else {
            $this->country = $country;
            $this->publication_country = $country ? [$country] : null;
        }

        // Status
        $this->status = $data['status'] ?? null;

        // Author
        $this->author = $this->normalizeToArray($data['author'] ?? []);

        // Subject area e thematic area - podem vir como arrays
        $this->subject_area = $this->normalizeToArray($data['thematic_area'] ?? []);
        $this->thematic_area = $this->normalizeToArray($data['thematic_area'] ?? []);
        $this->thematic_area_display = $this->normalizeToArray($data['thematic_area_display'] ?? []);

        // Descriptores e MeSH terms
        $this->descriptor = $this->normalizeToArray($data['descriptor'] ?? []);
        $this->mh = $this->normalizeToArray($data['mh'] ?? []);

        // Datas
        $this->created_date = $data['created_date'] ?? null;
        $this->updated_date = $data['updated_date'] ?? null;
        $this->publication_date = $data['publication_date'] ?? null;
        $this->publication_year = $data['publication_year'] ?? null;

        // Languages - podem vir como arrays
        $this->languages = $this->normalizeToArray($data['language'] ?? []);
        $this->language_display = $this->normalizeToArray($data['language_display'] ?? []);

        // Media type display
        $this->media_type_display = $this->normalizeToArray($data['media_type_display'] ?? []);
    }

    /**
     * Converte o DTO para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'media_type' => $this->media_type,
            'media_collection' => $this->media_collection,
            'country' => $this->country,
            'languages' => $this->languages,
            'status' => $this->status,
            'subject_area' => $this->subject_area,
            'thematic_area' => $this->thematic_area,
            'descriptor' => $this->descriptor,
            'mh' => $this->mh,
            'author' => $this->author,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
            'publication_date' => $this->publication_date,
            'publication_year' => $this->publication_year,
            'publication_country' => $this->publication_country,
            'language_display' => $this->language_display,
            'media_type_display' => $this->media_type_display,
            'thematic_area_display' => $this->thematic_area_display,
            'django_ct' => $this->django_ct,
            'django_id' => $this->django_id,
        ];
    }

    /**
     * Retorna apenas os campos principais para exibição
     */
    public function getDisplayData(): array
    {
        return [
            'title' => $this->title,
            'media_type' => $this->media_type,
            'media_collection' => $this->media_collection,
            'country' => $this->country,
            'languages' => $this->languages,
            'url' => $this->url,
            'author' => $this->author,
            'description' => $this->description,
        ];
    }

    /**
     * Verifica se o DTO é válido
     */
    public function isValid(): bool
    {
        return !empty($this->title) || !empty($this->id);
    }

    /**
     * Retorna as linguagens como string separada por vírgula
     */
    public function getLanguagesString(): string
    {
        if (empty($this->languages)) {
            return '';
        }
        return implode(', ', $this->languages);
    }

    /**
     * Retorna os autores como string separada por vírgula
     */
    public function getAuthorsString(): string
    {
        if (empty($this->author)) {
            return '';
        }
        return implode(', ', $this->author);
    }

    /**
     * Retorna os descritores como string separada por vírgula
     */
    public function getDescriptorsString(): string
    {
        if (empty($this->descriptor)) {
            return '';
        }
        return implode(', ', $this->descriptor);
    }

    /**
     * Retorna os termos MeSH como string separada por vírgula
     */
    public function getMeshString(): string
    {
        if (empty($this->mh)) {
            return '';
        }
        return implode(', ', $this->mh);
    }

    /**
     * Retorna a área temática formatada
     */
    public function getFormattedSubjectArea(): ?string
    {
        if (!empty($this->thematic_area_display)) {
            return $this->extractFirstValue($this->thematic_area_display);
        }
        if (!empty($this->subject_area)) {
            return $this->extractFirstValue($this->subject_area);
        }
        return null;
    }

    /**
     * Retorna o país formatado
     */
    public function getFormattedCountry(): ?string
    {
        if (!empty($this->publication_country)) {
            return $this->extractFirstValue($this->publication_country);
        }
        return $this->country;
    }

    /**
     * Retorna o tipo de mídia formatado
     */
    public function getFormattedMediaType(): ?string
    {
        if (!empty($this->media_type_display)) {
            return $this->extractFirstValue($this->media_type_display);
        }
        return $this->media_type;
    }

    /**
     * Retorna a data de criação formatada
     */
    public function getFormattedCreatedDate(): ?string
    {
        if (!$this->created_date) {
            return null;
        }

        // Se já está no formato YYYY-MM-DD, retorna como está
        if (preg_match('/^\d{8}$/', $this->created_date)) {
            return date('d/m/Y', strtotime($this->created_date));
        }

        // Tenta converter para data
        $timestamp = strtotime($this->created_date);
        return $timestamp ? date('d/m/Y', $timestamp) : $this->created_date;
    }

    /**
     * Retorna a data de atualização formatada
     */
    public function getFormattedUpdatedDate(): ?string
    {
        if (!$this->updated_date) {
            return null;
        }

        // Se já está no formato YYYY-MM-DD, retorna como está
        if (preg_match('/^\d{8}$/', $this->updated_date)) {
            return date('d/m/Y', strtotime($this->updated_date));
        }

        // Tenta converter para data
        $timestamp = strtotime($this->updated_date);
        return $timestamp ? date('d/m/Y', $timestamp) : $this->updated_date;
    }

    /**
     * Retorna a data de publicação formatada
     */
    public function getFormattedPublicationDate(): ?string
    {
        if (!$this->publication_date) {
            return null;
        }

        // Tenta converter para data
        $timestamp = strtotime($this->publication_date);
        return $timestamp ? date('d/m/Y', $timestamp) : $this->publication_date;
    }

    /**
     * Retorna o ano de publicação
     */
    public function getPublicationYear(): ?string
    {
        return $this->publication_year;
    }

    /**
     * Extrai o primeiro valor de um campo que pode ser array ou string
     */
    private function extractFirstValue($value): ?string
    {
        if (is_array($value) && !empty($value)) {
            return $value[0];
        }
        return $value ?: null;
    }

    /**
     * Normaliza um valor para array
     */
    private function normalizeToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && !empty($value)) {
            return [$value];
        }
        return [];
    }
}
