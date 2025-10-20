<?php
namespace BV\API;

if (!defined('ABSPATH'))
    exit;

/**
 * DTO para normalizar dados de eventos da API BVS Saúde
 */
final class EventDto
{
    public ?string $id;
    public ?string $title;
    public ?string $url;
    public ?string $description;
    public ?string $type;
    public ?string $country;
    public ?string $city;
    public ?string $address;
    public ?array $languages;
    public ?string $status;
    public $subject_area;
    public ?string $contact_email;
    public ?array $event_modality;
    public ?array $event_type;
    public ?array $target_groups;
    public ?array $keyword;
    public ?array $observations;
    public ?string $start_date;
    public ?string $end_date;
    public ?string $created_date;
    public ?string $updated_date;
    public ?string $publication_year;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? $data['django_id'] ?? null;
        $this->title = $data['title'] ?? null;

        // URL - pode vir como array de links
        $this->url = $this->extractFirstValue($data['link'] ?? null);

        // Description/Observations
        $this->observations = $this->normalizeToArray($data['observations'] ?? []);
        $this->description = $this->extractFirstValue($data['observations'] ?? null);

        // Tipo de evento - usar event_type
        $this->type = $this->extractFirstValue($data['event_type'] ?? null);

        // País - usar country
        $country = $data['country'] ?? null;
        if (is_array($country) && !empty($country)) {
            $this->country = $country[0];
        } else {
            $this->country = $country;
        }

        // Cidade e endereço
        $this->city = $this->extractFirstValue($data['city'] ?? null);
        $this->address = $this->extractFirstValue($data['address'] ?? null);

        // Status
        $this->status = $data['status'] ?? null;

        // Contact email
        $this->contact_email = $this->extractFirstValue($data['contact_email'] ?? null);

        // Event modality e type
        $this->event_modality = $this->normalizeToArray($data['event_modality'] ?? []);
        $this->event_type = $this->normalizeToArray($data['event_type'] ?? []);

        // Target groups
        $this->target_groups = $this->normalizeToArray($data['target_groups'] ?? []);

        // Keywords
        $this->keyword = $this->normalizeToArray($data['keyword'] ?? []);

        // Subject area - usar descriptor ou thematic_area_display
        $subjectArea = $data['descriptor'] ?? $data['mh'] ?? $data['thematic_area_display'] ?? null;
        if (is_array($subjectArea)) {
            $this->subject_area = $subjectArea;
        } else {
            $this->subject_area = $subjectArea;
        }

        // Datas
        $this->created_date = $data['created_date'] ?? null;
        $this->updated_date = $data['updated_date'] ?? null;
        $this->start_date = $data['start_date'] ?? null;
        $this->end_date = $data['end_date'] ?? null;
        $this->publication_year = $data['publication_year'] ?? null;

        // Languages - usar official_language_display
        $this->languages = $this->normalizeToArray($data['official_language_display'] ?? $data['official_language'] ?? []);
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
            'city' => $this->city,
            'address' => $this->address,
            'languages' => $this->languages,
            'status' => $this->status,
            'subject_area' => $this->subject_area,
            'contact_email' => $this->contact_email,
            'event_modality' => $this->event_modality,
            'event_type' => $this->event_type,
            'target_groups' => $this->target_groups,
            'keyword' => $this->keyword,
            'observations' => $this->observations,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
            'publication_year' => $this->publication_year,
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
            'city' => $this->city,
            'languages' => $this->languages,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
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
     * Retorna o tipo de evento formatado
     */
    public function getFormattedType(): string
    {
        if (!$this->type) {
            return 'Evento';
        }

        // Extrair apenas a parte em português se vier no formato "en^Congress|pt-br^Congresso|es^Congreso"
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
            'congress' => 'Congresso',
            'symposium' => 'Simpósio',
            'meeting' => 'Reunião',
            'seminar' => 'Seminário',
            'conference' => 'Conferência',
            'scientific conference' => 'Jornada',
            'course' => 'Curso',
            'scientific meeting' => 'Encontro',
            'workshop' => 'Workshop',
            'lecture' => 'Palestra',
            'forum' => 'Fórum',
            'training' => 'Capacitação',
            'colloquium' => 'Colóquio',
            'fair and exhibitions' => 'Feiras e Exposições',
            'round table' => 'Mesa Redonda',
            'tv show' => 'Programa de Televisão',
            'prize and awardships' => 'Prêmios e Concursos',
            'convention' => 'Convenção',
            'health campaings' => 'Campanhas de Saúde',
            'panel' => 'Painel',
        ];

        return $typeMap[strtolower($type)] ?? ucfirst($type);
    }

    /**
     * Retorna a modalidade do evento formatada
     */
    public function getFormattedModality(): string
    {
        if (!is_array($this->event_modality) || empty($this->event_modality)) {
            return '';
        }

        $modalityMap = [
            'in-person' => 'Presencial',
            'virtual' => 'Virtual',
            'hybrid' => 'Híbrido',
        ];

        $modalities = [];
        foreach ($this->event_modality as $modality) {
            $modalities[] = $modalityMap[strtolower($modality)] ?? ucfirst($modality);
        }

        return implode(', ', $modalities);
    }

    /**
     * Retorna a data de início formatada
     */
    public function getFormattedStartDate(): string
    {
        if (!$this->start_date) {
            return '';
        }

        try {
            $date = new \DateTime($this->start_date);
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return $this->start_date;
        }
    }

    /**
     * Retorna a data de fim formatada
     */
    public function getFormattedEndDate(): string
    {
        if (!$this->end_date) {
            return '';
        }

        try {
            $date = new \DateTime($this->end_date);
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return $this->end_date;
        }
    }

    /**
     * Retorna o período do evento (início - fim)
     */
    public function getFormattedPeriod(): string
    {
        $startDate = $this->getFormattedStartDate();
        $endDate = $this->getFormattedEndDate();

        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                return $startDate;
            }
            return $startDate . ' - ' . $endDate;
        }

        return $startDate ?: $endDate;
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
     * Retorna as palavras-chave como string
     */
    public function getKeywordsString(): string
    {
        if (!is_array($this->keyword) || empty($this->keyword)) {
            return '';
        }

        return implode(', ', $this->keyword);
    }

    /**
     * Retorna os grupos-alvo como string
     */
    public function getTargetGroupsString(): string
    {
        if (!is_array($this->target_groups) || empty($this->target_groups)) {
            return '';
        }

        return implode(', ', $this->target_groups);
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
