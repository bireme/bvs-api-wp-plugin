<?php
namespace BV\API;

if (!defined('ABSPATH'))
    exit;

/**
 * DTO para bases bibliográficas da BVS
 * Baseado na estrutura de resposta da API BVS para bibliographic databases
 */
final class BibliographicDatabaseDto
{
    public ?string $id = null;
    public ?string $django_id = null;
    public ?string $django_ct = null;
    public ?int $_version_ = null;
    public ?string $reference_title = null;
    public ?string $english_title = null;
    public ?string $reference_abstract = null;
    public ?array $abstract_language = null;
    public ?array $author = null;
    public ?string $publication_date = null;
    public ?string $publication_year = null;
    public ?array $publication_country = null;
    public ?array $publication_language = null;
    public ?array $publication_type = null;
    public ?array $database = null;
    public ?array $indexed_database = null;
    public ?int $status = null;
    public ?string $created_date = null;
    public ?string $updated_date = null;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->django_id = $data['django_id'] ?? null;
        $this->django_ct = $data['django_ct'] ?? null;
        $this->_version_ = $data['_version_'] ?? null;
        $this->reference_title = $data['reference_title'][0] ?? $data['reference_title'] ?? null;
        $this->english_title = $data['english_title'] ?? null;
        $this->reference_abstract = $data['reference_abstract'][0] ?? $data['reference_abstract'] ?? null;
        $this->abstract_language = $data['abstract_language'] ?? null;
        $this->author = $data['author'] ?? null;
        $this->publication_date = $data['publication_date'] ?? null;
        $this->publication_year = $data['publication_year'] ?? null;
        $this->publication_country = $data['publication_country'] ?? null;
        $this->publication_language = $data['publication_language'] ?? null;
        $this->publication_type = $data['publication_type'] ?? null;
        $this->database = $data['database'] ?? null;
        $this->indexed_database = $data['indexed_database'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->created_date = $data['created_date'] ?? null;
        $this->updated_date = $data['updated_date'] ?? null;
    }

    /**
     * Verifica se o DTO é válido
     */
    public function isValid(): bool
    {
        return !empty($this->reference_title) || !empty($this->english_title);
    }

    /**
     * Retorna o título principal (reference_title ou english_title)
     */
    public function getTitle(): ?string
    {
        return $this->reference_title ?: $this->english_title;
    }

    /**
     * Retorna o resumo/abstract principal
     */
    public function getAbstract(): ?string
    {
        if (is_array($this->reference_abstract)) {
            return $this->reference_abstract[0] ?? null;
        }
        return $this->reference_abstract;
    }

    /**
     * Retorna o país formatado
     */
    public function getFormattedCountry(): ?string
    {
        if (empty($this->publication_country) || !is_array($this->publication_country)) {
            return null;
        }

        $country = $this->publication_country[0] ?? null;
        if (!$country) {
            return null;
        }

        // Extrai o nome do país do formato "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
        if (preg_match('/^[^|]*\|pt-br\^([^|]*)/', $country, $matches)) {
            return $matches[1];
        }

        // Se não encontrar o formato pt-br, pega o primeiro
        if (preg_match('/^[^|]*\|[^|]*\^([^|]*)/', $country, $matches)) {
            return $matches[1];
        }

        return $country;
    }

    /**
     * Retorna o idioma formatado
     */
    public function getFormattedLanguage(): ?string
    {
        if (empty($this->publication_language) || !is_array($this->publication_language)) {
            return null;
        }

        $language = $this->publication_language[0] ?? null;
        if (!$language) {
            return null;
        }

        // Extrai o idioma do formato "en^Portuguese|es^Portugués|pt-br^Português|fr^Portugais"
        if (preg_match('/^[^|]*\|pt-br\^([^|]*)/', $language, $matches)) {
            return $matches[1];
        }

        // Se não encontrar o formato pt-br, pega o primeiro
        if (preg_match('/^[^|]*\|[^|]*\^([^|]*)/', $language, $matches)) {
            return $matches[1];
        }

        return $language;
    }

    /**
     * Retorna o tipo de publicação formatado
     */
    public function getFormattedPublicationType(): ?string
    {
        if (empty($this->publication_type) || !is_array($this->publication_type)) {
            return null;
        }

        $type = $this->publication_type[0] ?? null;
        if (!$type) {
            return null;
        }

        // Mapeia códigos de tipo para nomes legíveis
        $typeMap = [
            'S' => 'Periódico',
            'M' => 'Monografia',
            'T' => 'Tese',
            'N' => 'Não convencional',
            'MS' => 'Monografia de série',
            'TS' => 'Tese de série',
        ];

        return $typeMap[$type] ?? $type;
    }

    /**
     * Retorna a data de criação formatada
     */
    public function getFormattedCreatedDate(): ?string
    {
        if (!$this->created_date) {
            return null;
        }

        // Formato: YYYYMMDD
        if (strlen($this->created_date) === 8) {
            $year = substr($this->created_date, 0, 4);
            $month = substr($this->created_date, 4, 2);
            $day = substr($this->created_date, 6, 2);
            return "{$day}/{$month}/{$year}";
        }

        return $this->created_date;
    }

    /**
     * Retorna a data de atualização formatada
     */
    public function getFormattedUpdatedDate(): ?string
    {
        if (!$this->updated_date) {
            return null;
        }

        // Formato: YYYYMMDD
        if (strlen($this->updated_date) === 8) {
            $year = substr($this->updated_date, 0, 4);
            $month = substr($this->updated_date, 4, 2);
            $day = substr($this->updated_date, 6, 2);
            return "{$day}/{$month}/{$year}";
        }

        return $this->updated_date;
    }

    /**
     * Retorna a data de publicação formatada
     */
    public function getFormattedPublicationDate(): ?string
    {
        if (!$this->publication_date) {
            return null;
        }

        // Se for "s.n" ou "s.f", retorna como está
        if (in_array($this->publication_date, ['s.n', 's.f', 's.f.0000', 's.f.'])) {
            return $this->publication_date;
        }

        return $this->publication_date;
    }

    /**
     * Retorna o ano de publicação formatado
     */
    public function getFormattedPublicationYear(): ?string
    {
        if (!$this->publication_year) {
            return null;
        }

        // Se for "s.n" ou "s.f", retorna como está
        if (in_array($this->publication_year, ['s.n', 's.f', 's.f.0000', 's.f.'])) {
            return $this->publication_year;
        }

        return $this->publication_year;
    }

    /**
     * Retorna os autores como string
     */
    public function getAuthorsString(): ?string
    {
        if (empty($this->author) || !is_array($this->author)) {
            return null;
        }

        return implode('; ', $this->author);
    }

    /**
     * Retorna as bases de dados indexadas como string
     */
    public function getIndexedDatabasesString(): ?string
    {
        if (empty($this->indexed_database) || !is_array($this->indexed_database)) {
            return null;
        }

        return implode(', ', $this->indexed_database);
    }

    /**
     * Retorna o status formatado
     */
    public function getFormattedStatus(): ?string
    {
        if ($this->status === null) {
            return null;
        }

        $statusMap = [
            1 => 'Ativo',
            0 => 'Inativo',
            -3 => 'Excluído',
        ];

        return $statusMap[$this->status] ?? (string) $this->status;
    }

    /**
     * Retorna a URL para acessar o recurso (se disponível)
     */
    public function getUrl(): ?string
    {
        // Para bases bibliográficas, geralmente não há URL direta
        // Mas pode ser implementado se necessário
        return null;
    }
}
