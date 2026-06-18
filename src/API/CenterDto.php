<?php
namespace BV\API;

if (!defined('ABSPATH'))
    exit;

/**
 * DTO para normalizar dados de eventos da API BVS Saúde
 */
final class CenterDto
{
    public ?string $id;
    public ?string $title;
    public ?string $unit;
    public ?string $cooperative_center_code;

    public ?string $country;

    public ?string $state;
    public ?string $city;
    public ?string $address;
    public ?array $contact;

    public ?string $status;
    //public $institution_type;
    //public ?string $contact;
    public ?array $institution_thematic;
    public ?array $institution_type;

    public ?string $created_date;


    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? $data['django_id'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->unit = $this->extractFirstValue($data['unit'] ?? null);
        $this->cooperative_center_code = $data['cooperative_center_code'] ?? null;

        // País - usar country
        $country = $data['country'] ?? null;
        if (is_array($country) && !empty($country)) {
            $this->country = $country[0];
        } else {
            $this->country = $country;
        }

        // Cidade, endereço e contato
        $this->state = $this->extractFirstValue($data['state'] ?? null);
        $this->city = $this->extractFirstValue($data['city'] ?? null);
        $this->address = $this->extractFirstValue($data['address'] ?? null);

        //$this->address = $this->normalizeToArray($data['address'] ?? []);

        $this->contact = $this->normalizeToArray($data['contact'] ?? []);

        // Status
        $this->status = $data['status'] ?? null;

        // Center thematic and institutions types
        $this->institution_thematic = $this->normalizeToArray($data['institution_thematic'] ?? []);
        $this->institution_type = $this->normalizeToArray($data['institution_type'] ?? []);

        // Datas
        $this->created_date = $data['created_date'] ?? null;
  
        // Languages - usar official_language_display
        //$this->languages = $this->normalizeToArray($data['official_language_display'] ?? $data['official_language'] ?? []);
    }

    /**
     * Converte o DTO para array
     */
    public function toArray(): array
    {
        return [
        'id' => $this->id,
        'title' => $this->title,
        'unit' => $this->unit,
        'cooperative_center_code' => $this->cooperative_center_code,
        'country' => $this->country,
        'state' => $this->state,
        'city' => $this->city,
        'address' => $this->address,
        'contact' => $this->contact,
        'status' => $this->status,
        'institution_thematic' => $this->institution_thematic,
        'institution_type' => $this->institution_type,
        'created_date' => $this->created_date,
        ];
    }
    /*** Retorna apenas os campos principais para exibição */
    public function getDisplayData(): array
    {
        return [
            'title' => $this->title,
            'unit' => $this->unit,
            'cooperative_center_code' => $this->cooperative_center_code,
            'institution_type' => $this->institution_type,
            'address' => $this->address,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'contact' => $this->contact,
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
    public function getFormattedTypes(): string
    {
        if (!$this->institution_type) {
            return '';
        }

        // Se for array, juntar todos os elementos
        if (is_array($this->institution_type)) {
            $formattedAreas = [];
            foreach ($this->institution_type as $area) {
                $formattedArea = $this->extractPortugueseText($area);
                if ($formattedArea) {
                    $formattedAreas[] = $formattedArea;
                }
            }
            return implode(', ', $formattedAreas);
        }

        // Se for string, extrair parte em português
        return $this->extractPortugueseText($this->institution_type);
    }

    /**
     * Retorna o tipo de evento formatado
     */
    public function getFormattedType($type): string
    {

        $typeMap = [
            'CoordinatingCentersRg' => 'Centros Coordenadores Regionais',
            'CoordinatingCentersNc' => 'Centros Coordenadores Nacionais',
            'CoordinatingCentersTh' => 'Centros Coordenadores Temáticos',
            'CooperatingCenters' => 'Centros Cooperantes',
            'CooperatingCentersLILACS' => 'Centros Cooperantes LILACS',
            'CooperatingCentersLEYES' => 'Centros Cooperantes LEYES',
            'ParticipantsUnits' => 'Unidades Participantes',
            'EDITOR' => 'Editor',
            'VHLNetwork' => 'Rede BVS',
        ];

        return $typeMap[$type] ?? ucfirst($type);
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

  
    public function getTargetGroupsString(): string
    {
        if (!is_array($this->target_groups) || empty($this->target_groups)) {
            return '';
        }

        return implode(', ', $this->target_groups);
    }


    public function getContact(): string
    {
        if (!is_array($this->contact) || empty($this->contact)) {
            return '';
        }

        return implode(', ', $this->contact);
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
