<?php
namespace BV\Shortcodes;

use BV\API\BvsaludGenericClient;
use BV\API\WebResourceDto;
use BV\API\EventDto;
use BV\API\MultimediaDto;
use BV\API\LegislationDto;
use BV\API\BibliographicDatabaseDto;
use BV\API\JournalDto;
use BV\Shortcodes\Helpers\JournalToResource;
use BV\Shortcodes\Helpers\WebResourceToResource;
use BV\Shortcodes\Helpers\EventToResource;
use BV\Shortcodes\Helpers\LegislationToResource;
use BV\Shortcodes\Helpers\MultimediaToResource;
use BV\Support\ResourceCardDto;
use BV\Shortcodes\Classes\ResourcesParams;


if (!defined('ABSPATH'))
    exit;

final class BvsResourcesShortcode
{
    public function register(): void
    {
        add_shortcode('bvs_resources', [$this, 'render']);
    }

    public function render($atts, $content = ''): string
    {
        $atts = shortcode_atts([
            'search' => '',
            'searchTitle' => '',
            'type' => '',
            'limit' => 12,
            'max' => 50,
            'show_pagination' => 'false',
            'page' => 1,
            'show_filters' => 'false',
        ], $atts, 'bvs_resources');

        $resourcesConfig = \BV\Admin\SettingsPage::getResourcesConfig();
        $resourceConfig = null;
        foreach ($resourcesConfig as $resource) {
            if ($resource['resource'] === $atts['type']) {
                $resourceConfig = $resource;
                break;
            }
        }

        // Processar filtros dinâmicos baseados na configuração do recurso
        $appliedFilters = [];
        if ($resourceConfig && !empty($resourceConfig['filter_types'])) {
            foreach ($resourceConfig['filter_types'] as $filterType) {
                $facetKey = $filterType['key'] ?? '';

                if (empty($facetKey)) {
                    continue;
                }

                // Verificar se há valores na URL para este filtro
                if (isset($_GET[$facetKey]) && !empty($_GET[$facetKey])) {
                    if (is_array($_GET[$facetKey])) {
                        $values = array_map('sanitize_text_field', $_GET[$facetKey]);
                        $appliedFilters[$facetKey] = $values;
                    } else {
                        // Valor único
                        $appliedFilters[$facetKey] = sanitize_text_field($_GET[$facetKey]);
                    }
                }
            }
        }

        if (isset($_GET['bvsSearchTitle']) && !empty($_GET['bvsSearchTitle'])) {
            $atts['searchTitle'] = sanitize_text_field($_GET['bvsSearchTitle']);
        }
        if (isset($_GET['bvsTitle']) && !empty($_GET['bvsTitle'])) {
            $atts['searchTitle'] = sanitize_text_field($_GET['bvsTitle']);
        }
        if (isset($_GET['bvsLimit']) && !empty($_GET['bvsLimit'])) {
            $atts['limit'] = (int) $_GET['bvsLimit'];
        }
        if (isset($_GET['bvsMax']) && !empty($_GET['bvsMax'])) {
            $atts['max'] = (int) $_GET['bvsMax'];
        }
        if (isset($_GET['bvsPage'])) {
            $atts['page'] = max(1, (int) $_GET['bvsPage']);
        }

        $atts['limit'] = max(1, min(100, (int) $atts['limit']));
        $atts['max'] = max(1, min(500, (int) $atts['max']));
        $atts['page'] = max(1, (int) $atts['page']);
        $atts['show_pagination'] = $atts['show_pagination'] === 'true';
        $atts['show_filters'] = $atts['show_filters'] === 'true';

        $apiResponse = $this->getResources($atts['type'], new ResourcesParams(
            search: $atts['search'],
            searchTitle: $atts['searchTitle'],
            type: $atts['type'],
            limit: $atts['limit'],
            max: $atts['max'],
            show_pagination: $atts['show_pagination'],
            page: $atts['page'],
            showfilters: $atts['show_filters']
        ), $appliedFilters);




        $resources = [];

        // Verificar se há erro na resposta da API
        if (isset($apiResponse['error'])) {
            return '<div class="bvs-error">' . esc_html($apiResponse['error']) . '</div>';
        }

        // Verificar se docs existe e é um array
        if (!isset($apiResponse['docs']) || !is_array($apiResponse['docs'])) {
            return '<div class="bvs-error">Erro: Dados inválidos recebidos da API</div>';
        }

        //Converter para DTOs
        switch ($atts['type']) {
            case 'journals':
                $apiResponse['docs'] = array_map(function ($doc) {
                    if (!is_array($doc)) {
                        return null;
                    }
                    return new JournalDto($doc);
                }, $apiResponse['docs']);
                // Filtrar valores nulos
                $apiResponse['docs'] = array_filter($apiResponse['docs'], function ($doc) {
                    return $doc !== null;
                });
                $journalConverter = new JournalToResource();
                $resources = array_map(function ($journal) use ($journalConverter) {
                    return $journalConverter->convert($journal);
                }, $apiResponse['docs']);

                break;
            case 'events':
                $apiResponse['docs'] = array_map(function ($doc) {
                    if (!is_array($doc)) {
                        return null;
                    }
                    return new EventDto($doc);
                }, $apiResponse['docs']);
                // Filtrar valores nulos
                $apiResponse['docs'] = array_filter($apiResponse['docs'], function ($doc) {
                    return $doc !== null;
                });
                $eventConverter = new EventToResource();
                $resources = array_map(function ($event) use ($eventConverter) {
                    return $eventConverter->convert($event);
                }, $apiResponse['docs']);
                break;
            case 'webResources':
                $apiResponse['docs'] = array_map(function ($doc) {
                    if (!is_array($doc)) {
                        return null;
                    }
                    return new WebResourceDto($doc);
                }, $apiResponse['docs']);
                // Filtrar valores nulos
                $apiResponse['docs'] = array_filter($apiResponse['docs'], function ($doc) {
                    return $doc !== null;
                });
                $webResourceConverter = new WebResourceToResource();
                $resources = array_map(function ($webResource) use ($webResourceConverter) {
                    return $webResourceConverter->convert($webResource);
                }, $apiResponse['docs']);
                break;
            case 'legislations':
                $apiResponse['docs'] = array_map(function ($doc) {
                    if (!is_array($doc)) {
                        return null;
                    }
                    return new LegislationDto($doc);
                }, $apiResponse['docs']);
                // Filtrar valores nulos
                $apiResponse['docs'] = array_filter($apiResponse['docs'], function ($doc) {
                    return $doc !== null;
                });
                $legislationConverter = new LegislationToResource();
                $resources = array_map(function ($legislation) use ($legislationConverter) {
                    return $legislationConverter->convert($legislation);
                }, $apiResponse['docs']);
                break;
            case 'multimedia':
                $apiResponse['docs'] = array_map(function ($doc) {
                    if (!is_array($doc)) {
                        return null;
                    }
                    return new MultimediaDto($doc);
                }, $apiResponse['docs']);
                // Filtrar valores nulos
                $apiResponse['docs'] = array_filter($apiResponse['docs'], function ($doc) {
                    return $doc !== null;
                });
                $multimediaConverter = new MultimediaToResource();
                $resources = array_map(function ($multimedia) use ($multimediaConverter) {
                    return $multimediaConverter->convert($multimedia);
                }, $apiResponse['docs']);
                break;
            default:
                // No conversion needed for other types
                break;
        }

        if (empty($resources)) {
            $content = $this->renderEmpty();
        } else {
            $total = $apiResponse['total'] ?? count($resources);
            $content = $this->renderGenericGrid($resources, $atts, $total);
        }

        $showFiltersValue = !empty($atts['show_filters']) ? $atts['show_filters'] : $atts['show_filters'];
        $showFilters = filter_var($showFiltersValue, FILTER_VALIDATE_BOOLEAN);

        if ($showFilters) {
            return $this->renderWithFilters($content, $atts, $atts['type'], $apiResponse);
        }

        return $content;
    }



    /**
     * Renderiza layout com sidebar de filtros
     */
    private function renderWithFilters(string $content, array $atts, $type, $apiResponse): string
    {
        $filtersSidebar = $this->renderFiltersSidebar($atts, $type, $apiResponse);

        // Garantir que o CSS dos filtros seja carregado
        wp_enqueue_style('bv-public');

        $html = '<div class="bvs-container-with-filters">';
        $html .= '<div class="bvs-filters-sidebar">' . $filtersSidebar . '</div>';
        $html .= '<div class="bvs-content-area">' . $content . '</div>';
        $html .= '</div>';

        return $html;
    }



    /**
     * Renderiza a sidebar de filtros
     */
    private function renderFiltersSidebar(array $atts, $type, $apiResponse): string
    {
        // Pegar título atual (fixo)
        $currentTitle = $_GET['bvsTitle'] ?? $_GET['bvsSearchTitle'] ?? $atts['searchTitle'] ?? '';
        // Obter todos os recursos configurados
        $resourcesConfig = \BV\Admin\SettingsPage::getResourcesConfig();

        // Buscar a configuração do recurso específico
        $resourceConfig = null;
        foreach ($resourcesConfig as $resource) {
            if ($resource['resource'] === $type) {
                $resourceConfig = $resource;
                break;
            }
        }

        if (empty($resourceConfig)) {
            return '<div class="bvs-error">Recurso "' . esc_html($type) . '" não configurado</div>';
        }

        $resourceUrl = $resourceConfig['base_url'];
        $filterTypes = $resourceConfig['filter_types'] ?? [];

        $filters = [];
        if (!empty($filterTypes)) {
            $facetKeys = array_map(function ($filterType) {
                return $filterType['key'] ?? '';
            }, $filterTypes);

            $facetKeys = array_filter($facetKeys);


            if (!empty($facetKeys)) {
                $facetsResponse = $apiResponse;

                if (!is_array($facetsResponse)) {
                    return '<div class="bvs-error">Erro: Resposta da API inválida</div>';
                }

                foreach ($filterTypes as $filterType) {


                    $facetKey = $filterType['key'] ?? '';
                    $facetLabel = $filterType['label'] ?? '';

                    if (empty($facetKey)) {
                        continue;
                    }

                    $filterData = $this->buildFilterFromFacetResponse($facetsResponse, $facetKey, $facetLabel);

                    if (!empty($filterData)) {
                        $filters[] = $filterData;
                    }
                }
            }
        }

        $templatePath = trailingslashit(dirname(__DIR__, 1)) . 'Templates/resources-sidebar.php';

        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }

        return '<div class="bvs-error">Template não encontrado</div>';
    }

    /**
     * Extrai o valor de uma string multilíngue
     * 
     * @param string $value String que pode conter formato multilíngue (ex: "en^Chile|pt-br^Chile|es^Chile|fr^Chili")
     * @param string $lang Idioma preferido (padrão: 'pt-br')
     * @return string Valor extraído no idioma solicitado ou valor original se não for multilíngue
     */
    private function extractMultilangValue(string $value, string $lang = 'pt-br'): string
    {
        if (strpos($value, '^') === false || strpos($value, '|') === false) {
            return $value;
        }

        $translations = explode('|', $value);
        foreach ($translations as $translation) {
            $parts = explode('^', $translation, 2);
            if (count($parts) === 2) {
                $currentLang = trim($parts[0]);
                $translatedValue = trim($parts[1]);

                if ($currentLang === $lang) {
                    return $translatedValue;
                }
            }
        }

        if ($lang !== 'en') {
            foreach ($translations as $translation) {
                $parts = explode('^', $translation, 2);
                if (count($parts) === 2 && trim($parts[0]) === 'en') {
                    return trim($parts[1]);
                }
            }
        }

        if (!empty($translations)) {
            $parts = explode('^', $translations[0], 2);
            if (count($parts) === 2) {
                return trim($parts[1]);
            }
        }

        return $value;
    }

    /**
     * Constrói uma estrutura de filtro genérica a partir da resposta de facets da API
     * 
     * @param array $facetsResponse Resposta da API já contendo os facets
     * @param string $facetKey Chave do facet (ex: 'descriptor_filter', 'publication_country')
     * @param string $facetLabel Label do filtro para exibição
     * @return array|null Filtro genérico ou null se não houver dados
     */
    private function buildFilterFromFacetResponse(array $facetsResponse, string $facetKey, string $facetLabel): ?array
    {
        if (isset($facetsResponse['error'])) {
            return null;
        }

        $facetData = null;
        if (isset($facetsResponse['facets']['facet_fields'][$facetKey])) {
            $facetData = $facetsResponse['facets']['facet_fields'][$facetKey];
        } elseif (isset($facetsResponse['diaServerResponse'][0]['facet_counts']['facet_fields'][$facetKey])) {
            $facetData = $facetsResponse['diaServerResponse'][0]['facet_counts']['facet_fields'][$facetKey];
        } elseif (isset($facetsResponse['facet_counts']['facet_fields'][$facetKey])) {
            $facetData = $facetsResponse['facet_counts']['facet_fields'][$facetKey];
        } elseif (isset($facetsResponse['response']['facet_counts']['facet_fields'][$facetKey])) {
            $facetData = $facetsResponse['response']['facet_counts']['facet_fields'][$facetKey];
        }

        if (!$facetData || !is_array($facetData)) {
            return null;
        }

        $filterOptions = [];

        foreach ($facetData as $item) {
            if (is_array($item) && count($item) >= 2) {
                $rawValue = $item[0];
                $translatedLabel = $this->extractMultilangValue($rawValue, 'pt-br');

                $filterOptions[] = [
                    'key' => $rawValue,
                    'label' => $translatedLabel,
                    'count' => $item[1]
                ];
            }
        }

        if (empty($filterOptions)) {
            return null;
        }

        return [
            'name' => $facetLabel ?: $facetKey,
            'facetKey' => $facetKey,
            'filterOptions' => $filterOptions
        ];
    }


    /**
     * Renderiza um grid genérico com ResourceCardDto[]
     */
    private function renderGenericGrid(array $resources, array $atts, int $total): string
    {
        if (empty($resources)) {
            return '<div class="bvs-no-results">Nenhum resultado encontrado.</div>';
        }

        // Carrega o template genérico
        $templatePath = trailingslashit(dirname(__DIR__, 1)) . 'Templates/bvs-grid.php';

        if (file_exists($templatePath)) {
            ob_start();
            // Passar variáveis necessárias para o template
            $showPagination = $atts['show_pagination'];
            $currentPage = $atts['page'];
            $perPage = $atts['limit'];
            include $templatePath;
            return ob_get_clean();
        }

        // Fallback simples se o template não existir
        return '<div class="bvs-grid">' . implode('', $resources) . '</div>';
    }

    private function renderPagination(int $currentPage, int $perPage, int $total): string
    {
        $totalPages = ceil($total / $perPage);

        if ($totalPages <= 1)
            return '';

        $html = '<div class="bvs-pagination">';

        if ($currentPage > 1) {
            $firstUrl = add_query_arg('bvsPage', 1);
            $html .= '<a href="' . esc_url($firstUrl) . '" class="page-link">« Primeira</a>';
        }

        if ($totalPages <= 7) {
            $start = 1;
            $end = $totalPages;
            $showStartDots = false;
            $showEndDots = false;
        } else {
            // Mostra páginas em torno da atual
            $range = 2; // 2 páginas antes e depois da atual
            $start = max(1, $currentPage - $range);
            $end = min($totalPages, $currentPage + $range);

            // Ajusta se estiver muito próximo do início ou fim
            if ($start <= 2) {
                $start = 1;
                $end = min(5, $totalPages); // Mostra até 5 páginas do início
            }
            if ($end >= $totalPages - 1) {
                $end = $totalPages;
                $start = max(1, $totalPages - 4); // Mostra até 5 páginas do fim
            }

            $showStartDots = $start > 2;
            $showEndDots = $end < $totalPages - 1;
        }

        // Mostra ... no início se necessário
        if ($showStartDots) {
            $html .= '<span class="page-dots">...</span>';
        }

        // Links para páginas
        for ($i = $start; $i <= $end; $i++) {
            $class = $i === $currentPage ? 'page-link current' : 'page-link';

            if ($i === $currentPage) {
                $html .= '<span class="' . $class . '">' . $i . '</span>';
            } else {
                $pageUrl = add_query_arg('bvsPage', $i);
                $html .= '<a href="' . esc_url($pageUrl) . '" class="' . $class . '">' . $i . '</a>';
            }
        }

        // Mostra ... no fim se necessário
        if ($showEndDots) {
            $html .= '<span class="page-dots">...</span>';
        }

        // Mostra última página se não estiver no range
        if ($end < $totalPages) {
            if ($currentPage === $totalPages) {
                $html .= '<span class="page-link current">' . $totalPages . '</span>';
            } else {
                $lastUrl = add_query_arg('bvsPage', $totalPages);
                $html .= '<a href="' . esc_url($lastUrl) . '" class="page-link">' . $totalPages . '</a>';
            }
        }

        $html .= '</div>';

        return $html;
    }



    public function getResources($resourceType, ?ResourcesParams $params = null, array $appliedFilters = []): array
    {
        // Obter todos os recursos configurados
        $resourcesConfig = \BV\Admin\SettingsPage::getResourcesConfig();
        // Obter token
        $token = \BV\Admin\SettingsPage::getBvsaludToken();

        // Buscar a URL do recurso específico
        $resourceUrl = '';
        foreach ($resourcesConfig as $resource) {
            if ($resource['resource'] === $resourceType) {
                $resourceUrl = $resource['base_url'];
                break;
            }
        }
        if (empty($resourceUrl)) {
            return ['error' => "Recurso '{$resourceType}' não configurado"];
        }


        $client = new BvsaludGenericClient($resourceUrl, $token);
        $resourceConfig = null;
        foreach ($resourcesConfig as $resource) {
            if ($resource['resource'] === $resourceType) {
                $resourceConfig = $resource;
                break;
            }
        }

        $fqString = $this->buildFilters($params, $resourceType, $appliedFilters);

        $filters = $params ? [
            'q' => $params->search ?: '*:*',
            'count' => $params->limit,
            'start' => ($params->page - 1) * $params->limit,
            'fq' => $fqString
        ] : [];

        // Adicionar parâmetros de facet se show_filters estiver habilitado
        if ($params && $params->showfilters && !empty($resourceConfig['filter_types'])) {
            $facetKeys = array_map(function ($filterType) {
                return $filterType['key'] ?? '';
            }, $resourceConfig['filter_types']);

            // Remover chaves vazias
            $facetKeys = array_filter($facetKeys);

            if (!empty($facetKeys)) {
                $filters['facet'] = 'true';
                $filters['facet_field'] = implode(',', $facetKeys);
            }
        }

        // Buscar recursos
        $resources = $client->getResources($filters);

        return $resources;
    }

    /**
     * Constrói filtros fq dinamicamente a partir dos filtros aplicados
     * 
     * @param ResourcesParams $params Parâmetros da busca
     * @param string $resourceType Tipo de recurso
     * @param array $appliedFilters Filtros dinâmicos aplicados via URL
     * @return string String de filtros no formato Solr (fq)
     */
    private function buildFilters(ResourcesParams $params, string $resourceType, array $appliedFilters = []): string
    {
        $filters = [];

        foreach ($appliedFilters as $facetKey => $values) {
            if (is_array($values)) {
                $facetFilters = array_map(function ($value) use ($facetKey) {
                    $escapedValue = addslashes(trim($value));
                    return $facetKey . ':"' . $escapedValue . '"';
                }, $values);

                if (!empty($facetFilters)) {
                    $combinedFilter = '(' . implode(' OR ', $facetFilters) . ')';
                    $filters[] = $combinedFilter;
                }
            } else {
                $escapedValue = addslashes(trim($values));
                $facetKey = str_replace("_filter", "", $facetKey);
                $filterString = $facetKey . ':"' . $escapedValue . '"';
                $filters[] = $filterString;
            }
        }

        return implode(' AND ', $filters);
    }

    /**
     * Renderiza quando não há recursos
     */
    private function renderEmpty(): string
    {
        return '<div class="bvs-no-results">Nenhum resultado encontrado.</div>';
    }
}