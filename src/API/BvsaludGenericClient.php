<?php
namespace BV\API;

use BV\Support\Cache;

if (!defined('ABSPATH'))
    exit;

/**
 * Cliente genérico para a API BVS Saúde
 * Permite buscar recursos de qualquer endpoint com filtros opcionais
 */
final class BvsaludGenericClient
{
    private string $apiUrl;
    private string $token;
    private int $timeout;

    /**
     * Construtor do cliente genérico
     * 
     * @param string $apiUrl URL base da API
     * @param string $token Token de autenticação
     */
    public function __construct(string $apiUrl, string $token = '')
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->token = $token;
        $this->timeout = 15;
    }

    /**
     * Busca recursos da API com filtros opcionais
     * 
     * @param array $filters Filtros de busca (q, count, start, format, fq, etc.)
     * @return array Resposta da API em formato JSON
     */
    public function getResources(array $filters = []): array
    {
        if (empty($this->apiUrl)) {
            return ['error' => 'URL da API não configurada'];
        }

        // Parâmetros padrão
        $defaults = [
            'q' => '*:*',
            'count' => 20,
            'start' => 0,
            'format' => 'json',
            'fq' => '',
        ];

        // Mesclar filtros com padrões, removendo valores vazios
        $queryParams = array_filter(array_merge($defaults, $filters), function ($value) {
            return $value !== '' && $value !== null;
        });

        
        // Construir URL completa
        $searchUrl = $this->apiUrl . '/search/';
        $url = add_query_arg($queryParams, $searchUrl);

        return $this->makeRequest($url);
    }




    /**
     * Realiza uma requisição HTTP para a API
     * 
     * @param string $url URL completa da requisição
     * @return array Resposta da API
     */
    private function makeRequest(string $url): array
    {
        $headers = [
            'accept' => '*/*',
            'User-Agent' => 'BVSalud-Integrator-Plugin/' . (defined('BV_VERSION') ? BV_VERSION : '1.0.0')
        ];

        // Adicionar token se disponível
        if (!empty($this->token)) {
            $headers['apikey'] = $this->token;
        }

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
     * 
     * @param array $data Dados brutos da API
     * @return array Dados normalizados
     */
    private function normalizeApiResponse(array $data): array
    {
        // Formato atual da API BVS com diaServerResponse como array
        if (isset($data['diaServerResponse']) && is_array($data['diaServerResponse']) && !empty($data['diaServerResponse'])) {
            $diaResponse = $data['diaServerResponse'][0]; // Primeiro elemento do array
            
            return [
                'total' => $diaResponse['response']['numFound'] ?? 0,
                'start' => $diaResponse['response']['start'] ?? 0,
                'docs' => $diaResponse['response']['docs'] ?? [],
                'facets' => $diaResponse['facet_counts'] ?? [],
                'params' => $diaResponse['responseHeader']['params'] ?? [],
                'responseHeader' => $diaResponse['responseHeader'] ?? []
            ];
        }

        // Formato alternativo - diaServerResponse como objeto
        if (isset($data['diaServerResponse']) && is_array($data['diaServerResponse'])) {
            $response = $data['diaServerResponse'];
            
            return [
                'total' => $response['response']['numFound'] ?? 0,
                'start' => $response['response']['start'] ?? 0,
                'docs' => $response['response']['docs'] ?? [],
                'facets' => $response['facet_counts'] ?? [],
                'params' => $response['responseHeader']['params'] ?? [],
                'responseHeader' => $response['responseHeader'] ?? []
            ];
        }

        // Formato direto (fallback)
        return [
            'total' => $data['total'] ?? $data['numFound'] ?? count($data['docs'] ?? []),
            'start' => $data['start'] ?? 0,
            'docs' => $data['docs'] ?? $data,
            'facets' => $data['facets'] ?? $data['facet_counts'] ?? [],
            'params' => $data['params'] ?? [],
            'responseHeader' => $data['responseHeader'] ?? []
        ];
    }


    /**
     * Extrai o nome do país de uma string multilíngue
     * Formato: "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
     * 
     * @param string $countryRaw String com nomes em múltiplos idiomas
     * @param string $langPrefix Prefixo do idioma desejado (padrão: 'en')
     * @return string Nome do país no idioma solicitado
     */
    private function extractCountryName($countryRaw, string $langPrefix = 'en'): string
    {
        if (is_string($countryRaw)) {
            // Tentar pegar o nome no idioma solicitado
            if (preg_match('/' . preg_quote($langPrefix) . '\^([^|]+)/', $countryRaw, $matches)) {
                return trim($matches[1]);
            }
            // Se não tiver o idioma solicitado, pegar o primeiro nome (en^)
            if (preg_match('/en\^([^|]+)/', $countryRaw, $matches)) {
                return trim($matches[1]);
            }
        }
        // Se for string simples, retornar direto
        return is_string($countryRaw) ? $countryRaw : '';
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
     * 
     * @param string $langPrefix Prefixo do idioma para exibição (padrão: 'pt-br')
     * @return array Array com países contendo 'name', 'raw' e 'count'
     */
    public function getAvailableCountries(string $langPrefix = 'pt-br'): array
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

                    // Extrair nome no idioma solicitado do formato: "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
                    $countryName = $this->extractCountryName($countryRaw, $langPrefix);

                    if ($countryName && $count > 0) {
                        $countries[] = [
                            'name' => $countryName,
                            'raw' => $countryRaw,
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
     * Obtém a URL base da API
     * 
     * @return string URL base
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * Obtém o token de autenticação
     * 
     * @return string Token
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Define timeout para requisições
     * 
     * @param int $timeout Timeout em segundos
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }
}
