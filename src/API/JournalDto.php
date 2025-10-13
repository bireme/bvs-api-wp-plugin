<?php
namespace BV\API;

if (!defined('ABSPATH')) exit;

/**
 * DTO para normalizar dados de journals da API BVS Saúde
 */
final class JournalDto {
    public ?string $id;
    public ?string $title;
    public ?string $issn;
    public ?string $eissn;
    public ?string $publisher;
    public ?string $responsibility_mention;
    public ?string $country;
    public ?array $languages;
    public ?string $status;
    public ?string $subject_area;
    public ?string $url;
    public ?array $collections;
    public ?string $created_date;
    public ?string $updated_date;
    public ?string $initial_date;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? $data['journal_title'] ?? $this->extractFirstValue($data['shortened_title'] ?? null);
        
        // Normalizar campos que podem vir como arrays da API BVS
        $this->issn = $this->extractFirstValue($data['issn'] ?? null);
        $this->eissn = $this->extractFirstValue($data['eissn'] ?? $data['e_issn'] ?? null);
        $this->publisher = $this->extractFirstValue($data['publisher'] ?? null);
        $this->responsibility_mention = $this->extractFirstValue($data['responsibility_mention'] ?? null);
        $this->country = $this->extractFirstValue($data['country'] ?? null);
        $this->status = $this->extractFirstValue($data['status'] ?? null);

        $subjectArea = $data['subject_area'] ?? $data['subjectArea'] ?? $data['descriptor'] ?? $data['mh'] ?? null;
        $this->subject_area = $this->extractFirstValue($subjectArea);
        
        $this->url = $this->extractFirstValue($data['url'] ?? $data['journal_url'] ?? $data['link'] ?? null);
        $this->created_date = $this->extractFirstValue($data['created_date'] ?? $data['createdDate'] ?? null);
        $this->updated_date = $this->extractFirstValue($data['updated_date'] ?? $data['updatedDate'] ?? null);
        $this->initial_date = $this->extractFirstValue($data['initial_date'] ?? $data['initialDate'] ?? $data['init_date'] ?? null);

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
            'issn' => $this->issn,
            'eissn' => $this->eissn,
            'publisher' => $this->publisher,
            'responsibility_mention' => $this->responsibility_mention,
            'country' => $this->country,
            'languages' => $this->languages,
            'status' => $this->status,
            'subject_area' => $this->subject_area,
            'url' => $this->url,
            'collections' => $this->collections,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
            'initial_date' => $this->initial_date,
        ];
    }

    /**
     * Retorna apenas os campos principais para exibição
     */
    public function getDisplayData(): array {
        return [
            'title' => $this->title,
            'issn' => $this->issn,
            'publisher' => $this->publisher,
            'country' => $this->country,
            'languages' => $this->languages,
            'url' => $this->url,
        ];
    }


    public function isValid(): bool {
        return !empty($this->title) || !empty($this->id);
    }


    public function getPrimaryIssn(): ?string {
        return $this->issn ?: $this->eissn;
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
     * Formata uma data para exibição
     */
    public function formatDate(?string $date): ?string {
        if (!$date) {
            return null;
        }
        
        // Tenta diferentes formatos de data
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date; // Retorna original se não conseguir formatar
        }
        
        return date_i18n('d/m/Y', $timestamp);
    }

    /**
     * Retorna a data de criação formatada
     */
    public function getFormattedCreatedDate(): ?string {
        return $this->formatDate($this->created_date);
    }

    /**
     * Retorna a data de atualização formatada
     */
    public function getFormattedUpdatedDate(): ?string {
        return $this->formatDate($this->updated_date);
    }

    /**
     * Retorna a data inicial formatada
     */
    public function getFormattedInitialDate(): ?string {
        return $this->formatDate($this->initial_date);
    }

    /**
     * Extrai o nome do país em português
     * Formatos possíveis:
     * - "Brasil^iBR^pt^Brazil^iGB^en"
     * - "Brasil^pt^Brazil^en"
     * - "en^Brazil"
     * - "Brazil|pt-br"
     * - "Brasil"
     * Retorna apenas a versão em português
     */
    public function getFormattedCountry(): ?string {
        if (!$this->country) {
            return null;
        }

        $value = $this->country;
        
        // Remove códigos de idioma com pipe: "Brazil|pt-br" -> "Brazil"
        if (strpos($value, '|') !== false) {
            $value = explode('|', $value)[0];
        }
        
        // Se tem ^, processa as partes
        if (strpos($value, '^') !== false) {
            $parts = explode('^', $value);
            $languageCodes = ['pt', 'en', 'es', 'fr', 'de', 'it', 'ru', 'zh', 'ja', 'ar'];
            $longestValue = '';
            
            foreach ($parts as $part) {
                $trimmed = trim($part);
                
                // Ignora códigos vazios, códigos de país (iXX) e códigos de idioma
                if (empty($trimmed) || 
                    preg_match('/^i[A-Z]{2}$/i', $trimmed) || 
                    in_array(strtolower($trimmed), $languageCodes)) {
                    continue;
                }
                
                // Pega o valor mais longo (geralmente é o nome real)
                if (strlen($trimmed) > strlen($longestValue)) {
                    $longestValue = $trimmed;
                }
            }
            
            if ($longestValue) {
                return $longestValue;
            }
            
            // Fallback: retorna primeiro valor não-código
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (strlen($trimmed) > 2 && !in_array(strtolower($trimmed), $languageCodes)) {
                    return $trimmed;
                }
            }
        }
        
        return trim($value);
    }

    /**
     * Extrai o subject_area/thematic_area em português
     * Usa a mesma lógica do país
     */
    public function getFormattedSubjectArea(): ?string {
        if (!$this->subject_area) {
            return null;
        }

        $value = $this->subject_area;
        
        // Remove códigos de idioma com pipe
        if (strpos($value, '|') !== false) {
            $value = explode('|', $value)[0];
        }
        
        // Se tem ^, processa as partes
        if (strpos($value, '^') !== false) {
            $parts = explode('^', $value);
            $languageCodes = ['pt', 'en', 'es', 'fr', 'de', 'it', 'ru', 'zh', 'ja', 'ar'];
            $longestValue = '';
            
            foreach ($parts as $part) {
                $trimmed = trim($part);
                
                // Ignora códigos vazios, códigos de país (iXX) e códigos de idioma
                if (empty($trimmed) || 
                    preg_match('/^i[A-Z]{2}$/i', $trimmed) || 
                    in_array(strtolower($trimmed), $languageCodes)) {
                    continue;
                }
                
                // Pega o valor mais longo (geralmente é o nome real)
                if (strlen($trimmed) > strlen($longestValue)) {
                    $longestValue = $trimmed;
                }
            }
            
            if ($longestValue) {
                return $longestValue;
            }
            
            // Fallback: retorna primeiro valor não-código
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (strlen($trimmed) > 2 && !in_array(strtolower($trimmed), $languageCodes)) {
                    return $trimmed;
                }
            }
        }
        
        return trim($value);
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
