<?php
namespace BV\API;

if (!defined('ABSPATH'))
    exit;

/**
 * DTO para normalizar dados de legislações da API BVS Saúde
 */
final class LegislationDto
{
    public ?string $id;
    public ?string $title;
    public ?string $url;
    public ?string $description;
    public ?string $act_type;
    public ?string $act_number;
    public ?string $country;
    public ?string $scope;
    public ?string $scope_region;
    public ?string $scope_state;
    public ?string $scope_city;
    public ?array $languages;
    public ?string $status;
    public $descriptor;
    public ?string $issuer_organ;
    public ?string $source_name;
    public ?string $issue_date;
    public ?string $publication_date;
    public ?string $publication_year;
    public ?string $created_date;
    public ?string $updated_date;
    public ?string $official_ementa;
    public ?array $reference_title;
    public ?array $relationship_active;
    public ?array $collection;
    public ?array $thematic_area;
    public ?array $thematic_area_display;
    public ?array $indexed_database;
    public ?array $fulltext;

    /**
     * Idioma padrão para normalização de campos multilíngues
     */
    private string $defaultLanguage = 'en';

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? $data['django_id'] ?? null;

        // Título - usar reference_title como fallback se title estiver vazio
        $this->title = $data['title'] ?? null;
        if (empty($this->title) && !empty($data['reference_title']) && is_array($data['reference_title'])) {
            $this->title = $this->normalizeMultilangField($data['reference_title'][0], $this->defaultLanguage);
        }

        // URL - pode vir como array de links no fulltext
        $this->url = $this->extractFirstValue($data['fulltext'] ?? null);

        // Description - usar official_ementa se disponível
        $this->description = $this->extractFirstValue($data['official_ementa'] ?? null);
        $this->official_ementa = $this->extractFirstValue($data['official_ementa'] ?? null);

        // Tipo de ato - campo multilíngue
        $this->act_type = $this->normalizeMultilangField($this->extractFirstValue($data['act_type'] ?? null), $this->defaultLanguage);

        // Número do ato
        $this->act_number = $this->extractFirstValue($data['act_number'] ?? null);

        // País/Região - usar scope_region (campo multilíngue)
        $this->country = $this->normalizeMultilangField($this->extractFirstValue($data['scope_region'] ?? null), $this->defaultLanguage);
        $this->scope_region = $this->normalizeMultilangField($this->extractFirstValue($data['scope_region'] ?? null), $this->defaultLanguage);

        // Campos multilíngues que precisam ser normalizados
        $this->scope = $this->normalizeMultilangField($this->extractFirstValue($data['scope'] ?? null), $this->defaultLanguage);
        $this->scope_state = $this->normalizeMultilangField($this->extractFirstValue($data['scope_state'] ?? null), $this->defaultLanguage);
        $this->scope_city = $this->normalizeMultilangField($this->extractFirstValue($data['scope_city'] ?? null), $this->defaultLanguage);

        // Status
        $this->status = $this->extractFirstValue($data['status'] ?? null);

        // Descritores - usar descriptor ou mh
        $descriptor = $data['descriptor'] ?? $data['mh'] ?? null;
        if (is_array($descriptor)) {
            $this->descriptor = $descriptor;
        } else {
            $this->descriptor = $descriptor;
        }

        // Órgão emissor - campo multilíngue
        $this->issuer_organ = $this->normalizeMultilangField($this->extractFirstValue($data['issuer_organ'] ?? null), $this->defaultLanguage);

        // Fonte - campo multilíngue
        $this->source_name = $this->normalizeMultilangField($this->extractFirstValue($data['source_name'] ?? null), $this->defaultLanguage);

        // Datas
        $this->issue_date = $this->extractFirstValue($data['issue_date'] ?? null);
        $this->publication_date = $this->extractFirstValue($data['publication_date'] ?? null);
        $this->publication_year = $this->extractFirstValue($data['publication_year'] ?? null);
        $this->created_date = $this->extractFirstValue($data['created_date'] ?? null);
        $this->updated_date = $this->extractFirstValue($data['updated_date'] ?? null);

        // Títulos de referência
        $this->reference_title = $this->normalizeToArray($data['reference_title'] ?? []);

        // Relacionamentos ativos
        $this->relationship_active = $this->normalizeToArray($data['relationship_active'] ?? []);

        // Coleções
        $this->collection = $this->normalizeToArray($data['collection'] ?? []);

        // Área temática
        $this->thematic_area = $this->normalizeToArray($data['thematic_area'] ?? []);
        $this->thematic_area_display = $this->normalizeToArray($data['thematic_area_display'] ?? []);

        // Base de dados indexada
        $this->indexed_database = $this->normalizeToArray($data['indexed_database'] ?? []);

        // Texto completo
        $this->fulltext = $this->normalizeToArray($data['fulltext'] ?? []);

        // Languages - usar language
        $this->languages = $this->normalizeToArray($data['language'] ?? []);
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
            'act_type' => $this->act_type,
            'act_number' => $this->act_number,
            'country' => $this->country,
            'scope' => $this->scope,
            'scope_region' => $this->scope_region,
            'scope_state' => $this->scope_state,
            'scope_city' => $this->scope_city,
            'languages' => $this->languages,
            'status' => $this->status,
            'descriptor' => $this->descriptor,
            'issuer_organ' => $this->issuer_organ,
            'source_name' => $this->source_name,
            'issue_date' => $this->issue_date,
            'publication_date' => $this->publication_date,
            'publication_year' => $this->publication_year,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
            'official_ementa' => $this->official_ementa,
            'reference_title' => $this->reference_title,
            'relationship_active' => $this->relationship_active,
            'collection' => $this->collection,
            'thematic_area' => $this->thematic_area,
            'thematic_area_display' => $this->thematic_area_display,
            'indexed_database' => $this->indexed_database,
            'fulltext' => $this->fulltext,
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
            'act_type' => $this->act_type,
            'act_number' => $this->act_number,
            'country' => $this->country,
            'scope' => $this->scope,
            'issuer_organ' => $this->issuer_organ,
            'publication_date' => $this->publication_date,
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
     * Define o idioma padrão para normalização de campos multilíngues
     * 
     * @param string $languagePrefix Prefixo do idioma (ex: 'en', 'pt-br', 'es')
     */
    public function setDefaultLanguage(string $languagePrefix): void
    {
        $this->defaultLanguage = $languagePrefix;

        // Re-normalizar os campos multilíngues com o novo idioma
        $this->act_type = $this->normalizeMultilangField($this->act_type, $this->defaultLanguage);
        $this->country = $this->normalizeMultilangField($this->country, $this->defaultLanguage);
        $this->scope_region = $this->normalizeMultilangField($this->scope_region, $this->defaultLanguage);
        $this->scope = $this->normalizeMultilangField($this->scope, $this->defaultLanguage);
        $this->scope_state = $this->normalizeMultilangField($this->scope_state, $this->defaultLanguage);
        $this->scope_city = $this->normalizeMultilangField($this->scope_city, $this->defaultLanguage);
        $this->issuer_organ = $this->normalizeMultilangField($this->issuer_organ, $this->defaultLanguage);
        $this->source_name = $this->normalizeMultilangField($this->source_name, $this->defaultLanguage);
    }

    /**
     * Retorna o idioma padrão atual
     */
    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
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
        return $this->country ?: '';
    }

    /**
     * Retorna o escopo formatado
     */
    public function getFormattedScope(): string
    {
        return $this->scope ?: '';
    }

    /**
     * Retorna a região formatada
     */
    public function getFormattedRegion(): string
    {
        return $this->scope_region ?: '';
    }

    /**
     * Retorna o estado formatado
     */
    public function getFormattedState(): string
    {
        return $this->scope_state ?: '';
    }

    /**
     * Retorna a cidade formatada
     */
    public function getFormattedCity(): string
    {
        return $this->scope_city ?: '';
    }

    /**
     * Retorna os descritores como string
     */
    public function getDescriptorsString(): string
    {
        if (!is_array($this->descriptor) || empty($this->descriptor)) {
            return '';
        }

        return implode(', ', $this->descriptor);
    }

    /**
     * Retorna a área temática formatada
     */
    public function getFormattedSubjectArea(): string
    {
        if (!is_array($this->thematic_area_display) || empty($this->thematic_area_display)) {
            return '';
        }

        $formattedAreas = [];
        foreach ($this->thematic_area_display as $area) {
            // Normalizar cada área temática
            $formattedArea = $this->normalizeMultilangField($area, $this->defaultLanguage);
            if ($formattedArea) {
                $formattedAreas[] = $formattedArea;
            }
        }

        return implode(', ', $formattedAreas);
    }

    /**
     * Retorna o tipo de ato formatado
     */
    public function getFormattedActType(): string
    {
        if (!$this->act_type) {
            return 'Ato';
        }

        // O campo já foi normalizado no construtor
        $type = $this->act_type;

        $typeMap = [
            'resolução' => 'Resolução',
            'lei' => 'Lei',
            'decreto' => 'Decreto',
            'disposição' => 'Disposição',
            'decisão administrativa' => 'Decisão Administrativa',
            'resolução ss' => 'Resolução SS',
            'portaria' => 'Portaria',
            'decreto executivo' => 'Decreto Executivo',
            'resolução conjunta' => 'Resolução Conjunta',
            'documento bioético' => 'Documento Bioético',
            'ata' => 'Ata',
            'decreto acordo' => 'Decreto Acordo',
            'deliberação' => 'Deliberação',
            'convenção' => 'Convenção',
            'recomendação' => 'Recomendação',
            'tratado internacional' => 'Tratado Internacional',
            'declaração' => 'Declaração',
            'decreto lei' => 'Decreto Lei',
            'decreto supremo' => 'Decreto Supremo',
            'decreto de urgência' => 'Decreto de Urgência',
        ];

        return $typeMap[strtolower($type)] ?? ucfirst($type);
    }

    /**
     * Retorna o órgão emissor formatado
     */
    public function getFormattedIssuerOrgan(): string
    {
        return $this->issuer_organ ?: '';
    }

    /**
     * Retorna a fonte formatada
     */
    public function getFormattedSourceName(): string
    {
        return $this->source_name ?: '';
    }

    /**
     * Retorna a data de emissão formatada
     */
    public function getFormattedIssueDate(): string
    {
        if (!$this->issue_date) {
            return '';
        }

        try {
            $date = new \DateTime($this->issue_date);
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return $this->issue_date;
        }
    }

    /**
     * Retorna a data de publicação formatada
     */
    public function getFormattedPublicationDate(): string
    {
        if (!$this->publication_date) {
            return '';
        }

        try {
            $date = new \DateTime($this->publication_date);
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return $this->publication_date;
        }
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
     * Retorna o número do ato formatado
     */
    public function getFormattedActNumber(): string
    {
        if (!$this->act_number) {
            return '';
        }

        return $this->act_number;
    }

    /**
     * Retorna o título de referência formatado
     */
    public function getFormattedReferenceTitle(): string
    {
        if (!is_array($this->reference_title) || empty($this->reference_title)) {
            return '';
        }

        // Retornar o primeiro título de referência normalizado
        return $this->normalizeMultilangField($this->reference_title[0], $this->defaultLanguage);
    }

    /**
     * Normaliza campos multilíngues extraindo o texto do idioma especificado
     * Formato esperado: "es^Nacional|es^Nacional|en^National"
     * 
     * @param mixed $text Texto multilíngue (pode ser string, array ou null)
     * @param string $languagePrefix Prefixo do idioma desejado (padrão: 'en')
     * @return string Texto normalizado do idioma especificado
     */
    private function normalizeMultilangField($text, string $languagePrefix = 'en'): string
    {
        // Se for array, pega o primeiro item
        if (is_array($text)) {
            $text = !empty($text) ? (string) $text[0] : '';
        }

        // Converte para string se não for
        $text = (string) $text;

        if (empty($text)) {
            return '';
        }

        // Se não contém separadores, retorna o texto original
        if (strpos($text, '|') === false) {
            return $text;
        }

        // Separa por | e procura pelo idioma especificado
        $parts = explode('|', $text);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, $languagePrefix . '^') === 0) {
                return substr($part, strlen($languagePrefix) + 1); // Remove o prefixo do idioma
            }
        }

        // Se não encontrou o idioma especificado, retorna o primeiro disponível
        if (!empty($parts)) {
            $firstPart = trim($parts[0]);
            if (strpos($firstPart, '^') !== false) {
                $firstParts = explode('^', $firstPart, 2);
                return $firstParts[1] ?? $firstPart;
            }
            return $firstPart;
        }

        return $text;
    }


    /**
     * Extrai texto em português do formato multilíngue
     * @deprecated Use normalizeMultilangField() instead
     */
    private function extractPortugueseText(string $text): string
    {
        return $this->normalizeMultilangField($text, 'pt-br');
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
