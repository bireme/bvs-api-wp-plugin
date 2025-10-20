<?php
namespace BV\API;

if (!defined('ABSPATH'))
    exit;

/**
 * DTO para normalizar dados de recursos web da API BVS Saúde
 */
final class WebResourceDto
{
    public ?string $id;
    public ?string $title;
    public ?string $url;
    public ?string $description;
    public ?string $type;
    public ?string $country;
    public ?array $languages;
    public ?string $status;
    public $subject_area;
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

    public function __construct(array $data = [])
    {

        $this->id = $data['id'] ?? $data['django_id'] ?? null;
        $this->title = $data['title'] ?? null;

        // URL - pode vir como array de links
        $this->url = $this->extractFirstValue($data['link'] ?? null);

        // Abstract/Description
        $this->abstract = $data['abstract'] ?? null;
        $this->description = $this->abstract; // Usar abstract como description

        // Tipo de recurso - usar source_type_display
        $this->type = $this->extractFirstValue($data['source_type_display'] ?? $data['source_type'] ?? null);

        // País - usar country (pode vir como array)
        $country = $data['country'] ?? $data['publication_country'] ?? null;
        if (is_array($country) && !empty($country)) {
            $this->country = $country[0];
        } else {
            $this->country = $country;
        }

        // Status
        $this->status = $data['status'] ?? null;

        // Publisher/Originator
        $this->publisher = $this->extractFirstValue($data['originator'] ?? null);
        $this->institution = $this->publisher; // Usar originator como institution

        // Subject area - usar descriptor ou thematic_area_display (pode vir como array)
        $subjectArea = $data['descriptor'] ?? $data['mh'] ?? $data['thematic_area_display'] ?? null;
        if (is_array($subjectArea)) {
            $this->subject_area = $subjectArea;
        } else {
            $this->subject_area = $subjectArea;
        }

        // Keywords
        $this->keywords = $this->extractFirstValue($data['keyword'] ?? null);

        // Datas
        $this->created_date = $data['created_date'] ?? null;
        $this->updated_date = $data['updated_date'] ?? null;

        // Languages - usar language ou source_language_display
        $this->languages = $this->normalizeToArray($data['language'] ?? $data['source_language_display'] ?? []);

        // Collections - usar thematic_area
        $this->collections = $this->normalizeToArray($data['thematic_area'] ?? []);

        // Campos adicionais específicos da API
        $this->coverage = null; // Não disponível na API atual
        $this->format = $this->extractFirstValue($data['format'] ?? null);
        $this->language_interface = null; // Não disponível na API atual
        $this->update_frequency = null; // Não disponível na API atual
        $this->contact = null; // Não disponível na API atual
        $this->copyright = null; // Não disponível na API atual
        $this->license = null; // Não disponível na API atual
        $this->access_date = null; // Não disponível na API atual
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
    public function getDisplayData(): array
    {
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
    public function isValid(): bool
    {
        return !empty($this->title) || !empty($this->id);
    }

    /**
     * Retorna as linguagens como string separada por vírgula
     */
    public function getLanguagesString(): string
    {
        if (!is_array($this->languages) || empty($this->languages)) {
            return '';
        }

        // Extrair apenas a parte em português se vier no formato "en^English|pt-br^Português|es^Español"
        $formattedLanguages = [];
        foreach ($this->languages as $language) {
            if (strpos($language, '|') !== false) {
                $parts = explode('|', $language);
                foreach ($parts as $part) {
                    if (strpos($part, 'pt-br^') === 0) {
                        $formattedLanguages[] = substr($part, 6); // Remove "pt-br^"
                        break;
                    }
                }
            } else {
                $formattedLanguages[] = $language;
            }
        }

        return implode(', ', $formattedLanguages);
    }

    /**
     * Retorna o país formatado
     */
    public function getFormattedCountry(): string
    {
        if (!$this->country) {
            return '';
        }

        // Extrair apenas a parte em português se vier no formato "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
        if (strpos($this->country, '|') !== false) {
            $parts = explode('|', $this->country);
            foreach ($parts as $part) {
                if (strpos($part, 'pt-br^') === 0) {
                    return substr($part, 6); // Remove "pt-br^"
                }
            }
        }

        return $this->country;
    }

    /**
     * Retorna a área temática formatada
     */
    public function getFormattedSubjectArea(): string
    {
        if (!$this->subject_area) {
            return '';
        }

        // Se for array, juntar todos os elementos
        if (is_array($this->subject_area)) {
            $formattedAreas = [];
            foreach ($this->subject_area as $area) {
                $formattedArea = $this->extractPortugueseText($area);
                if ($formattedArea) {
                    $formattedAreas[] = $formattedArea;
                }
            }
            return implode(', ', $formattedAreas);
        }

        // Se for string, extrair parte em português
        return $this->extractPortugueseText($this->subject_area);
    }

    /**
     * Extrai texto em português do formato multilíngue
     */
    private function extractPortugueseText(string $text): string
    {
        if (strpos($text, '|') !== false) {
            $parts = explode('|', $text);
            foreach ($parts as $part) {
                if (strpos($part, 'pt-br^') === 0) {
                    return substr($part, 6); // Remove "pt-br^"
                }
            }
        }

        return $text;
    }

    /**
     * Retorna a data de criação formatada
     */
    public function getFormattedCreatedDate(): string
    {
        if (!$this->created_date) {
            return '';
        }

        // Formato: YYYYMMDD -> DD/MM/YYYY
        if (strlen($this->created_date) === 8) {
            $year = substr($this->created_date, 0, 4);
            $month = substr($this->created_date, 4, 2);
            $day = substr($this->created_date, 6, 2);
            return $day . '/' . $month . '/' . $year;
        }

        return $this->created_date;
    }

    /**
     * Retorna a data de atualização formatada
     */
    public function getFormattedUpdatedDate(): string
    {
        if (!$this->updated_date) {
            return '';
        }

        // Formato: YYYYMMDD -> DD/MM/YYYY
        if (strlen($this->updated_date) === 8) {
            $year = substr($this->updated_date, 0, 4);
            $month = substr($this->updated_date, 4, 2);
            $day = substr($this->updated_date, 6, 2);
            return $day . '/' . $month . '/' . $year;
        }

        return $this->updated_date;
    }

    /**
     * Retorna o tipo de recurso formatado
     */
    public function getFormattedType(): string
    {
        if (!$this->type) {
            return 'Recurso Web';
        }

        // Extrair apenas a parte em português se vier no formato "en^English|pt-br^Português|es^Español"
        $type = $this->type;
        if (strpos($type, '|') !== false) {
            $parts = explode('|', $type);
            foreach ($parts as $part) {
                if (strpos($part, 'pt-br^') === 0) {
                    $type = substr($part, 6); // Remove "pt-br^"
                    break;
                }
            }
        }

        $typeMap = [
            'editorial_resources' => 'Recursos Editoriais',
            'information_public' => 'Informações para o Público',
            'websites_institutional' => 'Sites Web Institucionais',
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

        return $typeMap[strtolower($type)] ?? ucfirst($type);
    }

    /**
     * Extrai o primeiro valor se for array, ou retorna o valor se for string/null
     */
    private function extractFirstValue($value): ?string
    {
        if (is_array($value)) {
            return !empty($value) ? (string) $value[0] : null;
        }
        return $value ? (string) $value : null;
    }

    /**
     * Normaliza valor para array
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
