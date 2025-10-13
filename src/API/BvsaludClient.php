<?php
namespace BV\API;

use BV\Support\Cache;
use BV\Admin\SettingsPage;

if (!defined('ABSPATH')) exit;

/**
 * Cliente para a API BVS Saúde - Search Journals endpoint
 */
final class BvsaludClient {
    private string $apiUrl;
    private string $token;
    private int $timeout;

    public function __construct(?string $apiUrl = null, ?string $token = null) {
        $this->apiUrl = $apiUrl ?: SettingsPage::getJournalsApiUrl();
        $this->token = $token ?: SettingsPage::getBvsaludToken();
        $this->timeout = 15;
    }

    /**
     * Busca journals por termo de pesquisa usando formato correto da API BVS
     */
    public function searchJournals(array $params = []): array {
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

        $queryParams = array_filter(array_merge($defaults, $params), function($value) {
            return $value !== '' && $value !== null;
        });

        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($queryParams, $baseUrl);
        
        $cacheKey = 'bv_bvs_journals_' . md5($url);



        return $this->makeRequest($url);

    }


    public function getJournalByIssn(string $issn): ?JournalDto {
        $results = $this->searchJournals(['q' => 'issn:' . $issn, 'count' => 1]);
        
        if (isset($results['error']) || empty($results['journals'])) {
            return null;
        }

        return new JournalDto($results['journals'][0]);
    }

    
    public function getJournalsByCountry(string $country, int $count = 20, int $start = 0): array {
       
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


    public function getJournalsBySubject(string $subject, int $limit = 20, int $start = 0): array {
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
    public function getJournalsByTitle(string $title, int $limit = 20, int $start = 0): array {
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
    public function listJournals(int $page = 1, int $perPage = 20): array {
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
    private function buildUrl(array $params): string {
        $baseUrl = rtrim($this->apiUrl, '/');
        return add_query_arg($params, $baseUrl);
    }

    /**
     * Faz a requisição HTTP para a API usando apikey no header
     */
    private function makeRequest(string $url): array {
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

        return $this->normalizeApiResponse($data);
    }

    /**
     * Normaliza a resposta da API BVS para um formato consistente
     */
    private function normalizeApiResponse(array $data): array {
        // Formato atual da API BVS com diaServerResponse
        if (isset($data['diaServerResponse'][0]['response']['docs'])) {
            $response = $data['diaServerResponse'][0]['response'];
            return [
                'journals' => $response['docs'],
                'resources' => $response['docs'], // Para compatibilidade com recursos web
                'total' => $response['numFound'] ?? count($response['docs'])
            ];
        }
        
        
        if (isset($data['response']['docs'])) {
            return [
                'journals' => $data['response']['docs'],
                'resources' => $data['response']['docs'], // Para compatibilidade com recursos web
                'total' => $data['response']['numFound'] ?? count($data['response']['docs'])
            ];
        }

        // Formato direto da API BVS title/v1/search
        if (isset($data['docs'])) {
            return [
                'journals' => $data['docs'],
                'resources' => $data['docs'], // Para compatibilidade com recursos web
                'total' => $data['numFound'] ?? count($data['docs'])
            ];
        }

        if (isset($data['data'])) {
            return [
                'journals' => is_array($data['data']) ? $data['data'] : [$data['data']],
                'resources' => is_array($data['data']) ? $data['data'] : [$data['data']], // Para compatibilidade com recursos web
                'total' => $data['total'] ?? count($data['data'])
            ];
        }

        if (isset($data['journals'])) {
            return $data;
        }

        if (isset($data['resources'])) {
            return $data;
        }

        if (is_array($data) && !empty($data) && isset($data[0]['title'])) {
            return [
                'journals' => $data,
                'resources' => $data, // Para compatibilidade com recursos web
                'total' => count($data)
            ];
        }

        return $data;
    }

    /**
     * Normaliza array de journals para DTOs
     */
    private function normalizeJournals(array $journals): array {
        return array_filter(
            array_map(function($journal) {
                $dto = new JournalDto($journal);
                return $dto->isValid() ? $dto : null;
            }, $journals)
        );
    }

   
    public function testConnection(): array {
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
    public function searchWebResources(array $params = []): array {
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

        $queryParams = array_filter(array_merge($defaults, $params), function($value) {
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
    public function getWebResourcesByCountry(string $country, int $count = 20): array {
        $countryFilter = $this->buildCountryFilter($country);
        
        $results = $this->searchWebResources([
            'q' => '*:*',
            'fq' => 'country:' . $countryFilter,
            'count' => $count
        ]);

        if (isset($results['error'])) {
            return [];
        }

        $resources = $results['resources'] ?? $results['docs'] ?? [];
        
        return $this->normalizeWebResources($resources);
    }

    /**
     * Busca recursos web por assunto
     */
    public function getWebResourcesBySubject(string $subject, int $limit = 20): array {
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
    public function getWebResourcesByTitle(string $title, int $limit = 20): array {
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
    public function getWebResourcesByType(string $type, int $limit = 20): array {
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
    public function listWebResources(int $page = 1, int $perPage = 20): array {
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
    public function searchWebResourcesByTerm(string $term, int $limit = 20): array {
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
    private function normalizeWebResources(array $resources): array {
        return array_filter(
            array_map(function($resource) {
                $dto = new WebResourceDto($resource);
                return $dto->isValid() ? $dto : null;
            }, $resources)
        );
    }

    /**
     * Obtém facet_fields da API para popular filtros
     */
    public function getFacetFields(array $facetFields = ['country']): array {
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
        return Cache::remember($cacheKey, function() use ($url) {
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
    public function getAvailableCountries(): array {
        $facets = $this->getFacetFields(['country']);
        
        if (isset($facets['error'])) {
            return [];
        }

        $countries = [];
        if (isset($facets['diaServerResponse'][0]['facet_counts']['facet_fields']['country'])) {
            $countryFacets = $facets['diaServerResponse'][0]['facet_counts']['facet_fields']['country'];
            
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
        usort($countries, function($a, $b) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });

        return $countries;
    }

    /**
     * Extrai o nome do país em português de uma string multilíngue
     * Formato: "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
     */
    private function extractCountryName($countryRaw): string {
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
    public function testWebResourcesConnection(): array {
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

    /**
     * Constrói filtro de país no formato da API BVS (case-insensitive)
     * Exemplo: "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
     */
    private function buildCountryFilter(string $country): string {
        // Normalizar para primeira letra maiúscula (case-insensitive)
        $country = ucfirst(strtolower(trim($country)));
        
        // Mapeamento de países em diferentes idiomas (baseado na API BVS)
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
        ];

        // Se o país está no mapeamento, usar o formato completo
        if (isset($countryMappings[$country])) {
            return $countryMappings[$country];
        }

        return '"' . $country . '"';
    }
}
