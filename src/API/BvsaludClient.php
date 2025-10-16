<?php
namespace BV\API;

use BV\Support\Cache;
use BV\Admin\SettingsPage;

if (!defined('ABSPATH'))
    exit;

/**
 * Cliente para a API BVS Saúde - Search Journals endpoint
 */
final class BvsaludClient
{
    private string $apiUrl;
    private string $token;
    private int $timeout;

    public function __construct(?string $apiUrl = null, ?string $token = null)
    {
        $this->apiUrl = $apiUrl ?: SettingsPage::getJournalsApiUrl();
        $this->token = $token ?: SettingsPage::getBvsaludToken();
        $this->timeout = 15;
    }

    /**
     * Construtor específico para legislações
     */
    public static function forLegislations(): self
    {
        return new self(SettingsPage::getLegislationsUrl());
    }

    /**
     * Construtor específico para eventos
     */
    public static function forEvents(): self
    {
        return new self(SettingsPage::getEventsUrl());
    }

    /**
     * Construtor específico para recursos web
     */
    public static function forWebResources(): self
    {
        return new self(SettingsPage::getLisUrl());
    }

    /**
     * Busca journals por termo de pesquisa usando formato correto da API BVS
     */
    public function searchJournals(array $params = []): array
    {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',        // termo de busca (sempre *:* ver com o Vini)
            'count' => 20,       // limite de resultados (count ao invés de limit)
            'start' => 0,        // offset para paginação (start ao invés de offset)
            'format' => 'json',  // formato da resposta
            'fq' => '',          // filtro query para país
        ];

        $queryParams = array_filter(array_merge($defaults, $params), function ($value) {
            return $value !== '' && $value !== null;
        });

        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($queryParams, $baseUrl);

        $cacheKey = 'bv_bvs_journals_' . md5($url);



        return $this->makeRequest($url);

    }


    public function getJournalByIssn(string $issn): ?JournalDto
    {
        $results = $this->searchJournals(['q' => 'issn:' . $issn, 'count' => 1]);

        if (isset($results['error']) || empty($results['journals'])) {
            return null;
        }

        return new JournalDto($results['journals'][0]);
    }


    public function getJournalsByCountry(string $country, int $count = 20, int $start = 0): array
    {

        $countryFilter = $this->buildCountryFilter($country);

        $results = $this->searchJournals([
            'q' => '*:*',
            'fq' => 'country:' . $countryFilter,
            'count' => $count,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return [
                'journals' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        return [
            'journals' => $this->normalizeJournals($results['journals'] ?? []),
            'total' => $results['total'] ?? 0
        ];
    }


    public function getJournalsBySubject(string $subject, int $limit = 20, int $start = 0): array
    {
        $results = $this->searchJournals([
            'q' => 'subject_area:"' . $subject . '"',
            'count' => $limit,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return [
                'journals' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        return [
            'journals' => $this->normalizeJournals($results['journals'] ?? []),
            'total' => $results['total'] ?? 0
        ];
    }

    /**
     * Busca journals por título
     */
    public function getJournalsByTitle(string $title, int $limit = 20, int $start = 0): array
    {
        $results = $this->searchJournals([
            'q' => 'title:"' . $title . '"',
            'count' => $limit,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return [
                'journals' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        return [
            'journals' => $this->normalizeJournals($results['journals'] ?? []),
            'total' => $results['total'] ?? 0
        ];
    }

    // /**
    //  * Lista todos os journals com paginação
    //  */
    public function listJournals(int $page = 1, int $perPage = 20): array
    {
        $start = ($page - 1) * $perPage;

        $results = $this->searchJournals([
            'q' => '*:*',
            'count' => $perPage,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return [
                'journals' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'error' => $results['error']
            ];
        }

        $journals = $results['journals'] ?? [];

        return [
            'journals' => $this->normalizeJournals($journals),
            'total' => $results['total'] ?? 0,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Constrói a URL com parâmetros
     */
    private function buildUrl(array $params): string
    {
        $baseUrl = rtrim($this->apiUrl, '/');
        return add_query_arg($params, $baseUrl);
    }

    /**
     * Faz a requisição HTTP para a API usando apikey no header
     */
    private function makeRequest(string $url): array
    {
        $headers = [
            'accept' => '*/*',
            'apikey' => $this->token,
            'User-Agent' => 'BVSalud-Integrator-Plugin/' . BV_VERSION
        ];

        $args = [
            'headers' => $headers,
            'timeout' => $this->timeout,
            'sslverify' => true,
            'method' => 'GET'
        ];

        $response = wp_remote_get($url, $args);



        if (is_wp_error($response)) {
            return ['error' => 'Erro de conexão: ' . $response->get_error_message()];
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($responseCode !== 200) {
            return [
                'error' => sprintf(
                    'Erro HTTP %d: %s',
                    $responseCode,
                    wp_remote_retrieve_response_message($response)
                )
            ];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Erro ao decodificar JSON: ' . json_last_error_msg()];
        }

        $normalized = $this->normalizeApiResponse($data);


        return $normalized;
    }

    /**
     * Normaliza a resposta da API BVS para um formato consistente
     */
    private function normalizeApiResponse(array $data): array
    {
        // Formato atual da API BVS com diaServerResponse
        if (isset($data['diaServerResponse'][0]['response']['docs'])) {
            $response = $data['diaServerResponse'][0]['response'];
            return [
                'journals' => $response['docs'],
                'resources' => $response['docs'],
                'events' => $response['docs'],
                'legislations' => $response['docs'],
                'databases' => $response['docs'],
                'multimedia' => $response['docs'],
                'total' => $response['numFound'] ?? count($response['docs'])
            ];
        }


        if (isset($data['response']['docs'])) {
            return [
                'journals' => $data['response']['docs'],
                'resources' => $data['response']['docs'],
                'events' => $data['response']['docs'],
                'legislations' => $data['response']['docs'],
                'databases' => $data['response']['docs'],
                'multimedia' => $data['response']['docs'],
                'total' => $data['response']['numFound'] ?? count($data['response']['docs'])
            ];
        }

        // Formato direto da API BVS title/v1/search
        if (isset($data['docs'])) {
            return [
                'journals' => $data['docs'],
                'resources' => $data['docs'],
                'databases' => $data['docs'],
                'events' => $data['docs'],
                'legislations' => $data['docs'],
                'multimedia' => $data['docs'],
                'total' => $data['numFound'] ?? count($data['docs'])
            ];
        }

        if (isset($data['data'])) {
            return [
                'journals' => is_array($data['data']) ? $data['data'] : [$data['data']],
                'resources' => is_array($data['data']) ? $data['data'] : [$data['data']],
                'events' => is_array($data['data']) ? $data['data'] : [$data['data']],
                'legislations' => is_array($data['data']) ? $data['data'] : [$data['data']],
                'databases' => is_array($data['data']) ? $data['data'] : [$data['data']],
                'multimedia' => is_array($data['data']) ? $data['data'] : [$data['data']],
                'total' => $data['total'] ?? count($data['data'])
            ];
        }

        if (isset($data['journals'])) {
            return $data;
        }

        if (isset($data['resources'])) {
            return $data;
        }

        if (isset($data['events'])) {
            return $data;
        }

        if (isset($data['legislations'])) {
            return $data;
        }

        if (isset($data['databases'])) {
            return $data;
        }

        if (isset($data['multimedia'])) {
            return $data;
        }

        if (is_array($data) && !empty($data) && isset($data[0]['title'])) {
            return [
                'journals' => $data,
                'resources' => $data,
                'events' => $data,
                'legislations' => $data,
                'databases' => $data,
                'multimedia' => $data,
                'total' => count($data)
            ];
        }

        return $data;
    }

    /**
     * Normaliza array de journals para DTOs
     */
    private function normalizeJournals(array $journals): array
    {
        return array_filter(
            array_map(function ($journal) {
                $dto = new JournalDto($journal);
                return $dto->isValid() ? $dto : null;
            }, $journals)
        );
    }


    public function testConnection(): array
    {
        if (!$this->apiUrl || !$this->token) {
            return [
                'success' => false,
                'message' => 'API URL ou token não configurados'
            ];
        }

        $testResult = $this->searchJournals(['q' => '*:*', 'count' => 1]);

        if (isset($testResult['error'])) {
            return [
                'success' => false,
                'message' => $testResult['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexão com BVS API estabelecida com sucesso',
            'total_journals' => $testResult['total'] ?? 0
        ];
    }

    // Métodos para Recursos Web

    /**
     * Busca recursos web por termo de pesquisa usando endpoint /search
     */
    public function searchWebResources(array $params = []): array
    {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',        // termo de busca
            'count' => 20,       // limite de resultados
            'start' => 0,        // offset para paginação
            'format' => 'json',  // formato da resposta
            'fq' => '',          // filtro query
        ];

        $queryParams = array_filter(array_merge($defaults, $params), function ($value) {
            return $value !== '' && $value !== null;
        });

        // Usar endpoint /search para recursos web
        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($queryParams, $baseUrl);

        return $this->makeRequest($url);
    }

    /**
     * Busca recursos web por país
     */
    public function getWebResourcesByCountry(string $country, int $count = 20): array
    {
        $countryFilter = $this->buildCountryFilter($country);


        $results = $this->searchWebResources([
            'q' => '*:*',
            'fq' => 'publication_country:' . $countryFilter,
            'count' => $count
        ]);

        if (isset($results['error'])) {
            return [
                'resources' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawResources = $results['resources'] ?? $results['docs'] ?? [];
        $normalizedResources = $this->normalizeWebResources($rawResources);

        return [
            'resources' => $normalizedResources,
            'total' => $results['total'] ?? count($normalizedResources)
        ];
    }

    /**
     * Busca recursos web por assunto
     */
    public function getWebResourcesBySubject(string $subject, int $limit = 20): array
    {
        $results = $this->searchWebResources([
            'q' => 'subject_area:"' . $subject . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [];
        }

        $resources = $results['resources'] ?? $results['docs'] ?? [];
        return $this->normalizeWebResources($resources);
    }

    /**
     * Busca recursos web por título
     */
    public function getWebResourcesByTitle(string $title, int $limit = 20): array
    {
        $results = $this->searchWebResources([
            'q' => 'title:"' . $title . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [];
        }

        $resources = $results['resources'] ?? $results['docs'] ?? [];
        return $this->normalizeWebResources($resources);
    }

    /**
     * Busca recursos web por tipo
     */
    public function getWebResourcesByType(string $type, int $limit = 20): array
    {
        $results = $this->searchWebResources([
            'q' => 'type:"' . $type . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [];
        }

        $resources = $results['resources'] ?? $results['docs'] ?? [];
        return $this->normalizeWebResources($resources);
    }

    /**
     * Lista todos os recursos web com paginação
     */
    public function listWebResources(int $page = 1, int $perPage = 20): array
    {
        $start = ($page - 1) * $perPage;

        $results = $this->searchWebResources([
            'q' => '*:*',
            'count' => $perPage,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return [
                'resources' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'error' => $results['error']
            ];
        }

        $resources = $results['resources'] ?? $results['docs'] ?? [];

        return [
            'resources' => $this->normalizeWebResources($resources),
            'total' => $results['total'] ?? 0,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Busca recursos web por termo específico
     */
    public function searchWebResourcesByTerm(string $term, int $limit = 20): array
    {
        $results = $this->searchWebResources([
            'q' => $term,
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [];
        }

        $resources = $results['resources'] ?? $results['docs'] ?? [];
        return $this->normalizeWebResources($resources);
    }

    /**
     * Normaliza array de recursos web para DTOs
     */
    private function normalizeWebResources(array $resources): array
    {
        return array_filter(
            array_map(function ($resource) {
                $dto = new WebResourceDto($resource);
                return $dto->isValid() ? $dto : null;
            }, $resources)
        );
    }

    /**
     * Obtém facet_fields da API para popular filtros
     */
    public function getFacetFields(array $facetFields = ['publication_country']): array
    {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',
            'count' => 0,  // Não precisamos dos resultados, só dos facets
            'format' => 'json',
            'facet' => 'true',
            'facet_field' => implode(',', $facetFields),
        ];

        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($defaults, $baseUrl);


        $cacheKey = 'bv_bvs_facets_' . md5($url);

        // Cache por 1 hora
        return Cache::remember($cacheKey, function () use ($url) {
            // Fazer request direto sem normalização para pegar facets
            $headers = [
                'accept' => '*/*',
                'apikey' => $this->token,
                'User-Agent' => 'BVSalud-Integrator-Plugin/' . BV_VERSION
            ];

            $args = [
                'headers' => $headers,
                'timeout' => $this->timeout,
                'sslverify' => true,
                'method' => 'GET'
            ];

            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                return ['error' => 'Erro de conexão: ' . $response->get_error_message()];
            }

            $responseCode = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if ($responseCode !== 200) {
                return [
                    'error' => sprintf(
                        'Erro HTTP %d: %s',
                        $responseCode,
                        wp_remote_retrieve_response_message($response)
                    )
                ];
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => 'Erro ao decodificar JSON: ' . json_last_error_msg()];
            }


            return $data;
        }, 3600);
    }

    /**
     * Obtém lista de países disponíveis para filtros
     */
    public function getAvailableCountries(): array
    {
        $facets = $this->getFacetFields(['publication_country']);

        if (isset($facets['error'])) {
            return [];
        }

        $countries = [];


        // Tentar diferentes estruturas de resposta
        $countryFacets = null;

        if (isset($facets['diaServerResponse'][0]['facet_counts']['facet_fields']['publication_country'])) {
            $countryFacets = $facets['diaServerResponse'][0]['facet_counts']['facet_fields']['publication_country'];
        } elseif (isset($facets['facet_counts']['facet_fields']['publication_country'])) {
            $countryFacets = $facets['facet_counts']['facet_fields']['publication_country'];
        } elseif (isset($facets['response']['facet_counts']['facet_fields']['publication_country'])) {
            $countryFacets = $facets['response']['facet_counts']['facet_fields']['publication_country'];
        }

        if ($countryFacets) {
            // Os facets vêm como array aninhado: [[nome, count], [nome, count], ...]
            foreach ($countryFacets as $facet) {
                if (is_array($facet) && isset($facet[0]) && isset($facet[1])) {
                    $countryRaw = $facet[0];
                    $count = $facet[1];

                    // Extrair nome em português do formato: "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
                    $countryName = $this->extractCountryName($countryRaw);

                    if ($countryName && $count > 0) {
                        $countries[] = [
                            'name' => $countryName,
                            'count' => $count
                        ];
                    }
                }
            }
        }

        // Ordenar por nome
        usort($countries, function ($a, $b) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });

        return $countries;
    }

    /**
     * Extrai o nome do país em português de uma string multilíngue
     * Formato: "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
     */
    private function extractCountryName($countryRaw): string
    {
        if (is_string($countryRaw)) {
            // Tentar pegar o nome em português (pt-br^)
            if (preg_match('/pt-br\^([^|]+)/', $countryRaw, $matches)) {
                return trim($matches[1]);
            }
            // Se não tiver pt-br, pegar o primeiro nome (en^)
            if (preg_match('/en\^([^|]+)/', $countryRaw, $matches)) {
                return trim($matches[1]);
            }
        }
        // Se for string simples, retornar direto
        return is_string($countryRaw) ? $countryRaw : '';
    }

    /**
     * Testa conexão com endpoint de recursos web
     */
    public function testWebResourcesConnection(): array
    {
        if (!$this->apiUrl || !$this->token) {
            return [
                'success' => false,
                'message' => 'API URL ou token não configurados'
            ];
        }

        $testResult = $this->searchWebResources(['q' => '*:*', 'count' => 1]);

        if (isset($testResult['error'])) {
            return [
                'success' => false,
                'message' => $testResult['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexão com BVS API (recursos web) estabelecida com sucesso',
            'total_resources' => $testResult['total'] ?? 0
        ];
    }


    // Métodos para Eventos

    /**
     * Busca eventos por termo de pesquisa usando endpoint /search
     */
    public function searchEvents(array $params = []): array
    {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',        // termo de busca
            'count' => 20,       // limite de resultados
            'start' => 0,        // offset para paginação
            'format' => 'json',  // formato da resposta
            'fq' => '',          // filtro query
        ];

        $queryParams = array_filter(array_merge($defaults, $params), function ($value) {
            return $value !== '' && $value !== null;
        });

        // Usar endpoint /search para eventos
        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($queryParams, $baseUrl);

        return $this->makeRequest($url);
    }

    /**
     * Busca eventos por país
     */
    public function getEventsByCountry(string $country, int $count = 20): array
    {
        $countryFilter = $this->buildCountryFilter($country);

        $results = $this->searchEvents([
            'q' => '*:*',
            'fq' => 'country:' . $countryFilter,
            'count' => $count
        ]);

        if (isset($results['error'])) {
            return [
                'events' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawEvents = $results['events'] ?? $results['docs'] ?? [];
        $normalizedEvents = $this->normalizeEvents($rawEvents);

        return [
            'events' => $normalizedEvents,
            'total' => $results['total'] ?? count($normalizedEvents)
        ];
    }

    /**
     * Busca eventos por assunto
     */
    public function getEventsBySubject(string $subject, int $limit = 20): array
    {
        $results = $this->searchEvents([
            'q' => 'descriptor:"' . $subject . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [
                'events' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawEvents = $results['events'] ?? $results['docs'] ?? [];
        return [
            'events' => $this->normalizeEvents($rawEvents),
            'total' => $results['total'] ?? count($rawEvents)
        ];
    }

    /**
     * Busca eventos por título
     */
    public function getEventsByTitle(string $title, int $limit = 20): array
    {
        $results = $this->searchEvents([
            'q' => 'title:"' . $title . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [
                'events' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawEvents = $results['events'] ?? $results['docs'] ?? [];
        return [
            'events' => $this->normalizeEvents($rawEvents),
            'total' => $results['total'] ?? count($rawEvents)
        ];
    }

    /**
     * Busca eventos por tipo
     */
    public function getEventsByType(string $type, int $limit = 20): array
    {
        $results = $this->searchEvents([
            'q' => 'event_type:"' . $type . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [
                'events' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawEvents = $results['events'] ?? $results['docs'] ?? [];
        return [
            'events' => $this->normalizeEvents($rawEvents),
            'total' => $results['total'] ?? count($rawEvents)
        ];
    }

    /**
     * Lista todos os eventos com paginação
     */
    public function listEvents(int $page = 1, int $perPage = 20): array
    {
        $start = ($page - 1) * $perPage;

        $results = $this->searchEvents([
            'q' => '*:*',
            'count' => $perPage,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return [
                'events' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'error' => $results['error']
            ];
        }

        $rawEvents = $results['events'] ?? $results['docs'] ?? [];

        return [
            'events' => $this->normalizeEvents($rawEvents),
            'total' => $results['total'] ?? 0,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Busca eventos por termo específico
     */
    public function searchEventsByTerm(string $term, int $limit = 20): array
    {
        $results = $this->searchEvents([
            'q' => $term,
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [
                'events' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawEvents = $results['events'] ?? $results['docs'] ?? [];
        return [
            'events' => $this->normalizeEvents($rawEvents),
            'total' => $results['total'] ?? count($rawEvents)
        ];
    }

    /**
     * Normaliza array de eventos para DTOs
     */
    private function normalizeEvents(array $events): array
    {
        return array_filter(
            array_map(function ($event) {
                $dto = new EventDto($event);
                return $dto->isValid() ? $dto : null;
            }, $events)
        );
    }

    /**
     * Obtém facet_fields da API para popular filtros de eventos
     */
    public function getEventFacetFields(array $facetFields = ['country', 'event_type', 'descriptor_filter']): array
    {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',
            'count' => 0,  // Não precisamos dos resultados, só dos facets
            'format' => 'json',
            'facet' => 'true',
            'facet_field' => implode(',', $facetFields),
        ];

        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($defaults, $baseUrl);

        $cacheKey = 'bv_bvs_event_facets_' . md5($url);

        // Cache por 1 hora
        return Cache::remember($cacheKey, function () use ($url) {
            // Fazer request direto sem normalização para pegar facets
            $headers = [
                'accept' => '*/*',
                'apikey' => $this->token,
                'User-Agent' => 'BVSalud-Integrator-Plugin/' . BV_VERSION
            ];

            $args = [
                'headers' => $headers,
                'timeout' => $this->timeout,
                'sslverify' => true,
                'method' => 'GET'
            ];

            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                return ['error' => 'Erro de conexão: ' . $response->get_error_message()];
            }

            $responseCode = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if ($responseCode !== 200) {
                return [
                    'error' => sprintf(
                        'Erro HTTP %d: %s',
                        $responseCode,
                        wp_remote_retrieve_response_message($response)
                    )
                ];
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => 'Erro ao decodificar JSON: ' . json_last_error_msg()];
            }

            return $data;
        }, 3600);
    }

    /**
     * Obtém lista de países disponíveis para filtros de eventos
     */
    public function getAvailableEventCountries(): array
    {
        $facets = $this->getEventFacetFields(['country']);

        if (isset($facets['error'])) {
            return [];
        }

        $countries = [];

        // Tentar diferentes estruturas de resposta
        $countryFacets = null;

        if (isset($facets['diaServerResponse'][0]['facet_counts']['facet_fields']['country'])) {
            $countryFacets = $facets['diaServerResponse'][0]['facet_counts']['facet_fields']['country'];
        } elseif (isset($facets['facet_counts']['facet_fields']['country'])) {
            $countryFacets = $facets['facet_counts']['facet_fields']['country'];
        } elseif (isset($facets['response']['facet_counts']['facet_fields']['country'])) {
            $countryFacets = $facets['response']['facet_counts']['facet_fields']['country'];
        }

        if ($countryFacets) {
            // Os facets vêm como array aninhado: [[nome, count], [nome, count], ...]
            foreach ($countryFacets as $facet) {
                if (is_array($facet) && isset($facet[0]) && isset($facet[1])) {
                    $countryRaw = $facet[0];
                    $count = $facet[1];

                    // Extrair nome em português do formato: "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
                    $countryName = $this->extractCountryName($countryRaw);

                    if ($countryName && $count > 0) {
                        $countries[] = [
                            'name' => $countryName,
                            'count' => $count
                        ];
                    }
                }
            }
        }

        // Ordenar por nome
        usort($countries, function ($a, $b) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });

        return $countries;
    }

    /**
     * Obtém lista de tipos de eventos disponíveis para filtros
     */
    public function getAvailableEventTypes(): array
    {
        // Tentar diferentes nomes de campos para tipos de eventos
        $possibleFields = ['event_type', 'type', 'eventType', 'eventtype', 'event_category', 'category'];

        foreach ($possibleFields as $fieldName) {
            $facets = $this->getEventFacetFields([$fieldName]);

            if (isset($facets['error'])) {
                continue;
            }

            $eventTypes = [];

            // Tentar diferentes estruturas de resposta
            $typeFacets = null;

            if (isset($facets['diaServerResponse'][0]['facet_counts']['facet_fields'][$fieldName])) {
                $typeFacets = $facets['diaServerResponse'][0]['facet_counts']['facet_fields'][$fieldName];
            } elseif (isset($facets['facet_counts']['facet_fields'][$fieldName])) {
                $typeFacets = $facets['facet_counts']['facet_fields'][$fieldName];
            } elseif (isset($facets['response']['facet_counts']['facet_fields'][$fieldName])) {
                $typeFacets = $facets['response']['facet_counts']['facet_fields'][$fieldName];
            } else {
                continue;
            }

            if ($typeFacets && is_array($typeFacets)) {
                foreach ($typeFacets as $facet) {
                    if (is_array($facet) && isset($facet[0]) && isset($facet[1])) {
                        $typeRaw = $facet[0];
                        $count = $facet[1];

                        // Extrair nome em português do formato: "en^Congress|pt-br^Congresso|es^Congreso"
                        $typeName = $this->extractPortugueseText($typeRaw);

                        if ($typeName && $count > 0) {
                            $eventTypes[] = [
                                'name' => $typeName,
                                'count' => $count
                            ];
                        }
                    }
                }
            }

            // Se encontrou tipos, retornar
            if (!empty($eventTypes)) {
                // Ordenar por nome
                usort($eventTypes, function ($a, $b) {
                    return strcmp(strtolower($a['name']), strtolower($b['name']));
                });

                return $eventTypes;
            }
        }

        // Se nenhum campo funcionou, retornar array vazio
        return [];
    }

    /**
     * Extrai texto em português do formato multilíngue
     */
    private function extractPortugueseText($text): string
    {
        // Se for array, pegar o primeiro elemento
        if (is_array($text)) {
            $text = !empty($text) ? $text[0] : '';
        }

        // Se não for string, converter para string
        if (!is_string($text)) {
            $text = (string) $text;
        }

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
     * Testa conexão com endpoint de eventos
     */
    public function testEventsConnection(): array
    {
        if (!$this->apiUrl || !$this->token) {
            return [
                'success' => false,
                'message' => 'API URL ou token não configurados'
            ];
        }

        $testResult = $this->searchEvents(['q' => '*:*', 'count' => 1]);

        if (isset($testResult['error'])) {
            return [
                'success' => false,
                'message' => $testResult['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexão com BVS API (eventos) estabelecida com sucesso',
            'total_events' => $testResult['total'] ?? 0
        ];
    }

    // ========================================
    // MÉTODOS PARA LEGISLAÇÕES
    // ========================================

    /**
     * Busca legislações por termo de pesquisa usando formato correto da API BVS
     */
    public function searchLegislations(array $params = []): array
    {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',        // termo de busca
            'count' => 20,       // limite de resultados
            'start' => 0,        // offset para paginação
            'format' => 'json',  // formato da resposta
            'fq' => '',          // filtro query
        ];

        $queryParams = array_filter(array_merge($defaults, $params), function ($value) {
            return $value !== '' && $value !== null;
        });

        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($queryParams, $baseUrl);


        $result = $this->makeRequest($url);


        return $result;
    }


    /**
     * Normaliza dados de legislações da API
     */
    private function normalizeLegislations(array $rawLegislations): array
    {
        $legislations = [];

        foreach ($rawLegislations as $legislation) {
            if (is_array($legislation)) {
                $legislations[] = new \BV\API\LegislationDto($legislation);
            }
        }

        return $legislations;
    }


    /**
     * Obtém tipos de atos disponíveis para legislações
     * Usa uma abordagem alternativa: busca legislações e extrai tipos únicos
     */
    public function getAvailableActTypes(): array
    {
        // Primeiro, tentar usar facets se disponível
        $facets = $this->getFacetFields(['act_type']);

        if (!isset($facets['error'])) {
            $actTypes = [];

            // Tentar diferentes estruturas de resposta
            $typeFacets = null;

            if (isset($facets['diaServerResponse'][0]['facet_counts']['facet_fields']['act_type'])) {
                $typeFacets = $facets['diaServerResponse'][0]['facet_counts']['facet_fields']['act_type'];
            } elseif (isset($facets['facet_counts']['facet_fields']['act_type'])) {
                $typeFacets = $facets['facet_counts']['facet_fields']['act_type'];
            } elseif (isset($facets['response']['facet_counts']['facet_fields']['act_type'])) {
                $typeFacets = $facets['response']['facet_counts']['facet_fields']['act_type'];
            }

            if ($typeFacets && is_array($typeFacets)) {
                foreach ($typeFacets as $facet) {
                    if (is_array($facet) && isset($facet[0]) && isset($facet[1])) {
                        $typeRaw = $facet[0];
                        $count = $facet[1];

                        // Extrair nome em português do formato: "pt-br^Resolução|es^Resolución|en^Resolution"
                        $typeName = $this->extractPortugueseText($typeRaw);

                        if ($typeName && $count > 0) {
                            $actTypes[] = [
                                'name' => $typeName,
                                'count' => $count
                            ];
                        }
                    }
                }

                if (!empty($actTypes)) {
                    // Ordenar por nome
                    usort($actTypes, function ($a, $b) {
                        return strcmp(strtolower($a['name']), strtolower($b['name']));
                    });

                    return $actTypes;
                }
            }
        }

        // Se facets não funcionaram, usar abordagem alternativa

        // Buscar legislações e extrair tipos únicos
        $results = $this->searchLegislations(['q' => '*:*', 'count' => 100]);

        if (isset($results['error'])) {
            return [];
        }

        $legislations = $results['legislations'] ?? [];
        $typeCounts = [];

        foreach ($legislations as $legislation) {
            if (isset($legislation['act_type'])) {
                $typeRaw = $legislation['act_type'];

                // Se for array, processar cada tipo
                if (is_array($typeRaw)) {
                    foreach ($typeRaw as $type) {
                        $typeName = $this->extractPortugueseText($type);
                        if ($typeName && !empty(trim($typeName))) {
                            if (!isset($typeCounts[$typeName])) {
                                $typeCounts[$typeName] = 0;
                            }
                            $typeCounts[$typeName]++;
                        }
                    }
                } else {
                    // Se for string simples
                    $typeName = $this->extractPortugueseText($typeRaw);
                    if ($typeName && !empty(trim($typeName))) {
                        if (!isset($typeCounts[$typeName])) {
                            $typeCounts[$typeName] = 0;
                        }
                        $typeCounts[$typeName]++;
                    }
                }
            }
        }

        $actTypes = [];
        foreach ($typeCounts as $name => $count) {
            $actTypes[] = [
                'name' => $name,
                'count' => $count
            ];
        }

        // Ordenar por nome
        usort($actTypes, function ($a, $b) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });

        return $actTypes;
    }

    /**
     * Testa conexão com endpoint de legislações
     */
    public function testLegislationsConnection(): array
    {
        if (!$this->apiUrl || !$this->token) {
            return [
                'success' => false,
                'message' => 'API URL ou token não configurados'
            ];
        }

        $testResult = $this->searchLegislations(['q' => '*:*', 'count' => 1]);

        if (isset($testResult['error'])) {
            return [
                'success' => false,
                'message' => $testResult['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexão com BVS API (legislações) estabelecida com sucesso',
            'total_legislations' => $testResult['total'] ?? 0
        ];
    }

    /**
     * Construtor específico para multimídias
     */
    public static function forMultimedia(): self
    {
        return new self(SettingsPage::getMultimediaUrl());
    }

    /**
     * Construtor específico para bases bibliográficas
     */
    public static function forBibliographicDatabases(): self
    {
        return new self(SettingsPage::getBibliographicDatabasesUrl());
    }

    /**
     * Busca bases bibliográficas por termo de pesquisa
     */
    public function searchBibliographicDatabases(array $params = []): array
    {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',
            'count' => 20,
            'start' => 0,
            'format' => 'json',
            'fq' => '',
        ];

        $queryParams = array_filter(array_merge($defaults, $params), function ($value) {
            return $value !== '' && $value !== null;
        });

        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($queryParams, $baseUrl);

        $cacheKey = 'bv_bvs_bibliographic_databases_' . md5($url);

        return $this->makeRequest($url);
    }

    /**
     * Busca bases bibliográficas por país
     */
    public function getBibliographicDatabasesByCountry(string $country, int $count = 20, int $start = 0): array
    {
        $countryFilter = $this->buildCountryFilter($country);

        $results = $this->searchBibliographicDatabases([
            'q' => '*:*',
            'fq' => 'publication_country:' . $countryFilter,
            'count' => $count,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return $results;
        }

        // Processar resposta da API
        $processedResults = $this->processBibliographicDatabasesResponse($results);

        return [
            'databases' => $processedResults['databases'],
            'total' => $processedResults['total']
        ];
    }

    /**
     * Processa a resposta da API para bases bibliográficas
     */
    private function processBibliographicDatabasesResponse(array $response): array
    {
        if (!isset($response['diaServerResponse'][0]['response']['docs'])) {
            return ['databases' => [], 'total' => 0];
        }

        $docs = $response['diaServerResponse'][0]['response']['docs'];
        $total = $response['diaServerResponse'][0]['response']['numFound'] ?? 0;

        $databases = [];
        foreach ($docs as $doc) {
            $dto = new BibliographicDatabaseDto($doc);
            if ($dto->isValid()) {
                $databases[] = $dto;
            }
        }

        return [
            'databases' => $databases,
            'total' => $total
        ];
    }

    /**
     * Testa conexão com endpoint de bases bibliográficas
     */
    public function testBibliographicDatabasesConnection(): array
    {
        if (!$this->apiUrl || !$this->token) {
            return [
                'success' => false,
                'message' => 'API URL ou token não configurados'
            ];
        }

        $testResult = $this->searchBibliographicDatabases(['q' => '*:*', 'count' => 1]);

        if (isset($testResult['error'])) {
            return [
                'success' => false,
                'message' => $testResult['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexão com BVS API (bases bibliográficas) estabelecida com sucesso',
            'total_databases' => $testResult['diaServerResponse'][0]['response']['numFound'] ?? 0
        ];
    }

    /**
     * Busca recursos multimídia por termo de pesquisa
     */
    public function searchMultimedia(array $params = []): array
    {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',
            'count' => 20,
            'start' => 0,
            'format' => 'json',
            'fq' => '',
        ];

        $queryParams = array_filter(array_merge($defaults, $params), function ($value) {
            return $value !== '' && $value !== null;
        });

        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($queryParams, $baseUrl);

        $cacheKey = 'bv_bvs_multimedia_' . md5($url);

        return $this->makeRequest($url);
    }

    /**
     * Busca recursos multimídia por país
     */
    public function getMultimediaByCountry(string $country, int $count = 20, int $start = 0): array
    {
        $countryFilter = $this->buildCountryFilter($country);

        $results = $this->searchMultimedia([
            'q' => '*:*',
            'fq' => 'publication_country:' . $countryFilter,
            'count' => $count,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return [
                'multimedia' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawMultimedia = $results['multimedia'] ?? [];
        $normalizedMultimedia = $this->normalizeMultimedia($rawMultimedia);

        return [
            'multimedia' => $normalizedMultimedia,
            'total' => $results['total'] ?? count($normalizedMultimedia)
        ];
    }

    /**
     * Busca recursos multimídia por assunto
     */
    public function getMultimediaBySubject(string $subject, int $limit = 20): array
    {
        $results = $this->searchMultimedia([
            'q' => 'descriptor:"' . $subject . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [
                'multimedia' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawMultimedia = $results['multimedia'] ?? [];
        return [
            'multimedia' => $this->normalizeMultimedia($rawMultimedia),
            'total' => $results['total'] ?? count($rawMultimedia)
        ];
    }

    /**
     * Busca recursos multimídia por título
     */
    public function getMultimediaByTitle(string $title, int $limit = 20): array
    {
        $results = $this->searchMultimedia([
            'q' => 'title:"' . $title . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [
                'multimedia' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawMultimedia = $results['multimedia'] ?? [];
        return [
            'multimedia' => $this->normalizeMultimedia($rawMultimedia),
            'total' => $results['total'] ?? count($rawMultimedia)
        ];
    }

    /**
     * Busca recursos multimídia por tipo de mídia
     */
    public function getMultimediaByType(string $type, int $limit = 20): array
    {
        $results = $this->searchMultimedia([
            'q' => 'media_type:"' . $type . '"',
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [
                'multimedia' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawMultimedia = $results['multimedia'] ?? [];
        return [
            'multimedia' => $this->normalizeMultimedia($rawMultimedia),
            'total' => $results['total'] ?? count($rawMultimedia)
        ];
    }

    /**
     * Lista todos os recursos multimídia com paginação
     */
    public function listMultimedia(int $page = 1, int $perPage = 20): array
    {
        $start = ($page - 1) * $perPage;

        $results = $this->searchMultimedia([
            'q' => '*:*',
            'count' => $perPage,
            'start' => $start
        ]);

        if (isset($results['error'])) {
            return [
                'multimedia' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'error' => $results['error']
            ];
        }

        $rawMultimedia = $results['multimedia'] ?? [];

        return [
            'multimedia' => $this->normalizeMultimedia($rawMultimedia),
            'total' => $results['total'] ?? 0,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Busca recursos multimídia por termo específico
     */
    public function searchMultimediaByTerm(string $term, int $limit = 20): array
    {
        $results = $this->searchMultimedia([
            'q' => $term,
            'count' => $limit
        ]);

        if (isset($results['error'])) {
            return [
                'multimedia' => [],
                'total' => 0,
                'error' => $results['error']
            ];
        }

        $rawMultimedia = $results['multimedia'] ?? [];
        return [
            'multimedia' => $this->normalizeMultimedia($rawMultimedia),
            'total' => $results['total'] ?? count($rawMultimedia)
        ];
    }

    /**
     * Normaliza array de recursos multimídia para DTOs
     */
    private function normalizeMultimedia(array $multimedia): array
    {
        return array_filter(
            array_map(function ($item) {
                $dto = new MultimediaDto($item);
                return $dto->isValid() ? $dto : null;
            }, $multimedia)
        );
    }

    /**
     * Obtém países disponíveis para recursos multimídia
     */
    public function getAvailableMultimediaCountries(): array
    {
        $results = $this->searchMultimedia(['q' => '*:*', 'count' => 1000]);

        if (isset($results['error'])) {
            return [];
        }

        $rawMultimedia = $results['multimedia'] ?? [];
        $countryCounts = [];

        foreach ($rawMultimedia as $item) {
            if (isset($item['publication_country'])) {
                $countryRaw = $item['publication_country'];

                // Se for array, processar cada país
                if (is_array($countryRaw)) {
                    foreach ($countryRaw as $country) {
                        $countryName = $this->extractPortugueseText($country);
                        if ($countryName && !empty(trim($countryName))) {
                            if (!isset($countryCounts[$countryName])) {
                                $countryCounts[$countryName] = 0;
                            }
                            $countryCounts[$countryName]++;
                        }
                    }
                } else {
                    // Se for string simples
                    $countryName = $this->extractPortugueseText($countryRaw);
                    if ($countryName && !empty(trim($countryName))) {
                        if (!isset($countryCounts[$countryName])) {
                            $countryCounts[$countryName] = 0;
                        }
                        $countryCounts[$countryName]++;
                    }
                }
            }
        }

        $countries = [];
        foreach ($countryCounts as $name => $count) {
            $countries[] = [
                'name' => $name,
                'count' => $count
            ];
        }

        // Ordenar por nome
        usort($countries, function ($a, $b) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });

        return $countries;
    }

    /**
     * Obtém tipos de mídia disponíveis
     */
    public function getAvailableMediaTypes(): array
    {
        $results = $this->searchMultimedia(['q' => '*:*', 'count' => 1000]);

        if (isset($results['error'])) {
            return [];
        }

        $rawMultimedia = $results['multimedia'] ?? [];
        $typeCounts = [];

        foreach ($rawMultimedia as $item) {
            if (isset($item['media_type'])) {
                $typeRaw = $item['media_type'];

                // Se for array, processar cada tipo
                if (is_array($typeRaw)) {
                    foreach ($typeRaw as $type) {
                        $typeName = $this->extractPortugueseText($type);
                        if ($typeName && !empty(trim($typeName))) {
                            if (!isset($typeCounts[$typeName])) {
                                $typeCounts[$typeName] = 0;
                            }
                            $typeCounts[$typeName]++;
                        }
                    }
                } else {
                    // Se for string simples
                    $typeName = $this->extractPortugueseText($typeRaw);
                    if ($typeName && !empty(trim($typeName))) {
                        if (!isset($typeCounts[$typeName])) {
                            $typeCounts[$typeName] = 0;
                        }
                        $typeCounts[$typeName]++;
                    }
                }
            }
        }

        $mediaTypes = [];
        foreach ($typeCounts as $name => $count) {
            $mediaTypes[] = [
                'name' => $name,
                'count' => $count
            ];
        }

        // Ordenar por nome
        usort($mediaTypes, function ($a, $b) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });

        return $mediaTypes;
    }

    /**
     * Testa conexão com endpoint de multimídia
     */
    public function testMultimediaConnection(): array
    {
        if (!$this->apiUrl || !$this->token) {
            return [
                'success' => false,
                'message' => 'API URL ou token não configurados'
            ];
        }

        $testResult = $this->searchMultimedia(['q' => '*:*', 'count' => 1]);

        if (isset($testResult['error'])) {
            return [
                'success' => false,
                'message' => $testResult['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexão com BVS API (multimídia) estabelecida com sucesso',
            'total_multimedia' => $testResult['total'] ?? 0
        ];
    }

    /**
     * Constrói filtro de país no formato da API BVS (case-insensitive)
     * Suporta múltiplos países separados por vírgula
     */
    private function buildCountryFilter(string $country): string
    {
        $countryMappings = [
            'Brazil' => '"en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"',
            'Brasil' => '"en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"',
            'Argentina' => '"en^Argentina|pt-br^Argentina|es^Argentina|fr^Argentine"',
            'Chile' => '"en^Chile|pt-br^Chile|es^Chile|fr^Chili"',
            'Colombia' => '"en^Colombia|pt-br^Colômbia|es^Colombia|fr^Colombie"',
            'Colômbia' => '"en^Colombia|pt-br^Colômbia|es^Colombia|fr^Colombie"',
            'Mexico' => '"en^Mexico|pt-br^México|es^Mexico|fr^Mexique"',
            'México' => '"en^Mexico|pt-br^México|es^Mexico|fr^Mexique"',
            'Peru' => '"en^Peru|pt-br^Peru|es^Perú|fr^Pérou"',
            'Uruguay' => '"en^Uruguay|pt-br^Uruguai|es^Uruguay|fr^Uruguay"',
            'Uruguai' => '"en^Uruguay|pt-br^Uruguai|es^Uruguay|fr^Uruguay"',
            'Venezuela' => '"en^Venezuela|pt-br^Venezuela|es^Venezuela|fr^Venezuela"',
            'Canada' => '"en^Canada|pt-br^Canadá|es^Canada|fr^Canada"',
            'Canadá' => '"en^Canada|pt-br^Canadá|es^Canada|fr^Canada"',
            'United states' => '"en^United States|pt-br^Estados Unidos da América|es^Estados Unidos|fr^États Unis"',
            'Estados unidos' => '"en^United States|pt-br^Estados Unidos da América|es^Estados Unidos|fr^États Unis"',
            'Eua' => '"en^United States|pt-br^Estados Unidos da América|es^Estados Unidos|fr^États Unis"',
            'United kingdom' => '"en^United kingdom|pt-br^Reino Unido|es^Reino Unido"',
            'Reino unido' => '"en^United kingdom|pt-br^Reino Unido|es^Reino Unido"',
            'Germany' => '"en^Germany|pt-br^Alemanha|es^Alemania"',
            'Alemanha' => '"en^Germany|pt-br^Alemanha|es^Alemania"',
            'Netherlands' => '"en^Netherlands|pt-br^Países Baixos|es^Paises Bajos"',
            'Países baixos' => '"en^Netherlands|pt-br^Países Baixos|es^Paises Bajos"',
            'Holanda' => '"en^Netherlands|pt-br^Países Baixos|es^Paises Bajos"',
            'France' => '"en^France|pt-br^França|es^Francia"',
            'França' => '"en^France|pt-br^França|es^Francia"',
            'Spain' => '"en^Spain|pt-br^Espanha|es^España"',
            'Espanha' => '"en^Spain|pt-br^Espanha|es^España"',
            'Switzerland' => '"en^Switzerland|pt-br^Suiça|es^Suiza"',
            'Suíça' => '"en^Switzerland|pt-br^Suiça|es^Suiza"',
            'Italy' => '"en^Italy|pt-br^Itália|es^Italia"',
            'Itália' => '"en^Italy|pt-br^Itália|es^Italia"',
            'Japan' => '"en^Japan|pt-br^Japão|es^Japon"',
            'Japão' => '"en^Japan|pt-br^Japão|es^Japon"',
            'Australia' => '"en^Australia|pt-br^Australia|es^Australia"',
            'India' => '"en^India|pt-br^Índia|es^India"',
            'Índia' => '"en^India|pt-br^Índia|es^India"',
            'China' => '"en^China|pt-br^China|es^China"',
            'Bolivia' => '"en^Bolivia|pt-br^Bolívia|es^Bolivia|fr^Bolivie"',
            'Bolívia' => '"en^Bolivia|pt-br^Bolívia|es^Bolivia|fr^Bolivie"',
        ];

        // Se houver múltiplos países (separados por vírgula)
        if (strpos($country, ',') !== false) {
            $countries = array_map('trim', explode(',', $country));
            $filters = [];

            foreach ($countries as $c) {
                $c = ucfirst(strtolower(trim($c)));
                if (isset($countryMappings[$c])) {
                    $filters[] = $countryMappings[$c];
                } else {
                    $filters[] = '"' . $c . '"';
                }
            }

            // Retorna com OR: (country1 OR country2 OR country3)
            return '(' . implode(' OR ', $filters) . ')';
        }

        // País único
        $country = ucfirst(strtolower(trim($country)));
        if (isset($countryMappings[$country])) {
            return $countryMappings[$country];
        }

        return '"' . $country . '"';
    }
}
