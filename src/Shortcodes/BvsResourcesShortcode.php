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


if (!defined('ABSPATH')) exit;

final class BvsResourcesShortcode {
    public function register(): void {
        add_shortcode('bvs_resources', [$this, 'render']);
    }

    public function render($atts, $content = ''): string {
            $atts = shortcode_atts([
                'country' => '',
                'subject' => '',
                'search' => '',
                'searchTitle' => '',
                'type' => '',
                'limit' => 12,
                'max' => 50,
                'show_pagination' => 'false',
                'page' => 1,
                'show_filters' => 'false',
                'resource' => '',
            ], $atts, 'bvs_resources');

            // Parâmetros da URL sobrescrevem os do shortcode
            $urlParams = [
                'bvsCountry' => 'country',
                'bvsSubject' => 'subject',
                'bvsSearchTitle' => 'searchTitle',
                'bvsTitle' => 'searchTitle', // Alias para searchTitle
                'bvsType' => 'type',
                'bvsLimit' => 'limit',
                'bvsMax' => 'max',
            ];

            foreach ($urlParams as $urlKey => $attrKey) {
                if (isset($_GET[$urlKey]) && !empty($_GET[$urlKey])) {
                    $atts[$attrKey] = sanitize_text_field($_GET[$urlKey]);
                }
            }
            
            if (isset($_GET['bvsPage'])) {
                $atts['page'] = max(1, (int) $_GET['bvsPage']);
            }
            
            // Processar checkboxes de países
            if (isset($_GET['bvsCountries']) && is_array($_GET['bvsCountries'])) {
                $selectedCountries = array_map('sanitize_text_field', $_GET['bvsCountries']);
                $atts['country'] = implode(',', $selectedCountries);
            }
            
            // Processar checkboxes de tipos de evento
            if (isset($_GET['bvsTypes']) && is_array($_GET['bvsTypes'])) {
                $selectedTypes = array_map('sanitize_text_field', $_GET['bvsTypes']);
                $atts['type'] = implode(',', $selectedTypes);
            }
            
            $atts['limit'] = max(1, min(100, (int) $atts['limit']));
            $atts['max'] = max(1, min(500, (int) $atts['max']));
            $atts['page'] = max(1, (int) $atts['page']);
            $atts['show_pagination'] = $atts['show_pagination'] === 'true';
            $atts['show_filters'] = $atts['show_filters'] === 'true';



            $content = $this->getResources($atts['type'], new ResourcesParams(
                country: $atts['country'],
                subject: $atts['subject'],
                search: $atts['search'],
                searchTitle: $atts['searchTitle'],
                type: $atts['type'],
                limit: $atts['limit'],
                max: $atts['max'],
                show_pagination: $atts['show_pagination'],
                page: $atts['page'],
                showfilters: $atts['show_filters']
            ));


            $resources = [];
            
            // Verificar se há erro na resposta da API
            if (isset($content['error'])) {
                return '<div class="bvs-error">' . esc_html($content['error']) . '</div>';
            }
            
            // Verificar se docs existe e é um array
            if (!isset($content['docs']) || !is_array($content['docs'])) {
                return '<div class="bvs-error">Erro: Dados inválidos recebidos da API</div>';
            }
            
            //Converter para DTOs
            switch ($atts['type']) {
                case 'journals':
                    $content['docs'] = array_map(function($doc) {
                        if (!is_array($doc)) {
                            return null;
                        }
                        return new JournalDto($doc);
                    }, $content['docs']);
                    // Filtrar valores nulos
                    $content['docs'] = array_filter($content['docs'], function($doc) {
                        return $doc !== null;
                    });
                    $journalConverter = new JournalToResource();
                    $resources = array_map(function($journal) use ($journalConverter) {
                        return $journalConverter->convert($journal);
                    }, $content['docs']);

                    break;
                case 'events':
                    $content['docs'] = array_map(function($doc) {
                        if (!is_array($doc)) {
                            return null;
                        }
                        return new EventDto($doc);
                    }, $content['docs']);
                    // Filtrar valores nulos
                    $content['docs'] = array_filter($content['docs'], function($doc) {
                        return $doc !== null;
                    });
                    $eventConverter = new EventToResource();
                    $resources = array_map(function($event) use ($eventConverter) {
                        return $eventConverter->convert($event);
                    }, $content['docs']);
                    break;
                case 'webResources':
                    $content['docs'] = array_map(function($doc) {
                        if (!is_array($doc)) {
                            return null;
                        }
                        return new WebResourceDto($doc);
                    }, $content['docs']);
                    // Filtrar valores nulos
                    $content['docs'] = array_filter($content['docs'], function($doc) {
                        return $doc !== null;
                    });
                    $webResourceConverter = new WebResourceToResource();
                    $resources = array_map(function($webResource) use ($webResourceConverter) {
                        return $webResourceConverter->convert($webResource);
                    }, $content['docs']);
                    break;
                case 'legislations':
                    $content['docs'] = array_map(function($doc) {
                        if (!is_array($doc)) {
                            return null;
                        }
                        return new LegislationDto($doc);
                    }, $content['docs']);
                    // Filtrar valores nulos
                    $content['docs'] = array_filter($content['docs'], function($doc) {
                        return $doc !== null;
                    });
                    $legislationConverter = new LegislationToResource();
                    $resources = array_map(function($legislation) use ($legislationConverter) {
                        return $legislationConverter->convert($legislation);
                    }, $content['docs']);
                    break;
                case 'multimedia':
                    $content['docs'] = array_map(function($doc) {
                        if (!is_array($doc)) {
                            return null;
                        }
                        return new MultimediaDto($doc);
                    }, $content['docs']);
                    // Filtrar valores nulos
                    $content['docs'] = array_filter($content['docs'], function($doc) {
                        return $doc !== null;
                    });
                    $multimediaConverter = new MultimediaToResource();
                    $resources = array_map(function($multimedia) use ($multimediaConverter) {
                        return $multimediaConverter->convert($multimedia);
                    }, $content['docs']);
                    break;
                default:
                    // No conversion needed for other types
                    break;
            }

            if (empty($resources)) {
                $content = $this->renderEmpty();
            } else {
                $total = $content['total'] ?? count($resources);
                $content = $this->renderGenericGrid($resources, $atts, $total);
            }

            $showFiltersValue = !empty($atts['show_filters']) ? $atts['show_filters'] : $atts['show_filters'];
            $showFilters = filter_var($showFiltersValue, FILTER_VALIDATE_BOOLEAN);

            if ($showFilters) {
                return $this->renderWithFilters($content, $atts, $atts['type']);
            }

            return $content;
    }



     /**
     * Renderiza layout com sidebar de filtros
     */
    private function renderWithFilters(string $content, array $atts, $type): string {
        $filtersSidebar = $this->renderFiltersSidebar($atts, $type);
        
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
    private function renderFiltersSidebar(array $atts, $type): string {
        // Pegar valores atuais dos filtros (da URL ou do shortcode)
        $currentTitle = $_GET['bvsTitle'] ?? $_GET['bvsSearchTitle'] ?? $atts['searchTitle'] ?? '';
        $currentCountry = $_GET['bvsCountry'] ?? $atts['country'] ?? '';
        $currentSubject = $_GET['bvsSubject'] ?? $atts['subject'] ?? '';
        
        ob_start();
        ?>
        <div class="bvs-filters-box">
            <h3 class="bvs-filters-title">Filtros de Busca</h3>
            
            <form method="get" class="bvs-filters-form" id="bvsFiltersForm">
                <!-- Preservar page_id e outros parâmetros necessários -->
                <?php if (isset($_GET['page_id'])): ?>
                    <input type="hidden" name="page_id" value="<?php echo esc_attr($_GET['page_id']); ?>">
                <?php endif; ?>
                
                <!-- Preservar slug da página -->
                <?php if (isset($_GET['pagename'])): ?>
                    <input type="hidden" name="pagename" value="<?php echo esc_attr($_GET['pagename']); ?>">
                <?php endif; ?>
                
                <!-- Busca por Título -->
                <div class="bvs-filter-group">
                    <label for="bvsTitle" class="bvs-filter-label">Buscar por Título:</label>
                    <input 
                        type="text" 
                        id="bvsTitle" 
                        name="bvsTitle" 
                        class="bvs-filter-input"
                        placeholder="Digite o título..."
                        value="<?php echo esc_attr($currentTitle); ?>"
                    >
                </div>
                
                <!-- Filtros de País -->
                <div class="bvs-filter-group">
                    <label class="bvs-filter-label">Países:</label>
                    
                    <?php
                    // Obter países disponíveis da API
                    // Obter todos os recursos configurados
                    $resourcesConfig = \BV\Admin\SettingsPage::getResourcesConfig();
                    // Obter token
                    $token = \BV\Admin\SettingsPage::getBvsaludToken();
                    
                    // Buscar a URL do recurso específico
                    $resourceUrl = '';
                    foreach ($resourcesConfig as $resource) {
                        if ($resource['resource'] === $type) {
                            $resourceUrl = $resource['base_url'];
                            break;
                        }
                    }
                    if (empty($resourceUrl)) {
                        return '<div class="bvs-error">Recurso "' . esc_html($type) . '" não configurado</div>';
                    }
                    

                    // Criar cliente genérico
                    $client = new BvsaludGenericClient($resourceUrl, $token);
                    $availableCountries = $client->getAvailableCountries();
                    $selectedCountries = !empty($currentCountry) ? explode(',', $currentCountry) : [];
                    ?>
                    
                    <div class="bvs-checkbox-container">
                        <?php
                        if (!empty($availableCountries)) {
                            foreach ($availableCountries as $country) {
                                $countryName = $country['name'];
                                $countryRaw = $country['raw'];
                                $countryCount = $country['count'];
                                $isChecked = in_array($countryRaw, $selectedCountries);
                                ?>
                                <label class="bvs-checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        name="bvsCountries[]" 
                                        value="<?php echo esc_attr($countryRaw); ?>"
                                        <?php echo $isChecked ? 'checked' : ''; ?>
                                        class="bvs-checkbox"
                                    >
                                    <span class="bvs-checkbox-label">
                                        <?php echo esc_html($countryName); ?>
                                        <small class="bvs-count">(<?php echo $countryCount; ?>)</small>
                                    </span>
                                </label>
                                <?php
                            }
                        } else {
                            ?>
                            <p class="bvs-no-countries">Nenhum país disponível</p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Botões -->
                <div class="bvs-filter-actions">
                    <button type="submit" class="bvs-btn-filter bvs-btn-primary">
                        🔍 Buscar
                    </button>
                    <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" class="bvs-btn-filter bvs-btn-secondary">
                        ✕ Limpar
                    </a>
                </div>
                
                <!-- Filtros ativos -->
                <?php if (!empty($currentTitle) || !empty($currentCountry) || !empty($currentSubject)): ?>
                    <div class="bvs-active-filters">
                        <strong>Filtros ativos:</strong>
                        <?php if (!empty($currentTitle)): ?>
                            <span class="bvs-filter-tag">
                                Título: <?php echo esc_html($currentTitle); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsTitle')); ?>" class="bvs-remove-filter">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($currentCountry)): ?>
                            <span class="bvs-filter-tag">
                                País: <?php echo esc_html($currentCountry); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsCountry')); ?>" class="bvs-remove-filter">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($currentSubject)): ?>
                            <span class="bvs-filter-tag">
                                Área: <?php echo esc_html($currentSubject); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsSubject')); ?>" class="bvs-remove-filter">×</a>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
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

    private function renderPagination(int $currentPage, int $perPage, int $total): string {
        $totalPages = ceil($total / $perPage);
        
        if ($totalPages <= 1) return '';
        
        $html = '<div class="bvs-pagination">';
        
        // Link para primeira página (se não estiver na primeira)
        if ($currentPage > 1) {
            $firstUrl = add_query_arg('bvsPage', 1);
            $html .= '<a href="' . esc_url($firstUrl) . '" class="page-link">« Primeira</a>';
        }
        
        // Calcula quais páginas mostrar em torno da página atual
        if ($totalPages <= 7) {
            // Se tem 7 ou menos páginas, mostra todas
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



    public function getResources($resourceType, ?ResourcesParams $params = null): array {
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
        

        // Criar cliente genérico
        $client = new BvsaludGenericClient($resourceUrl, $token);
        
        // Converter ResourcesParams para array
        $filters = $params ? [
            'q' => $params->search ?: '*:*',
            'count' => $params->limit,
            'start' => ($params->page - 1) * $params->limit,
            'fq' => $this->buildFilters($params, $resourceType)
        ] : [];
        
        // Buscar recursos
        $resources = $client->getResources($filters);
        
        return $resources;
    }

    /**
     * Constrói filtros fq a partir do objeto ResourcesParams
     */
    private function buildFilters(ResourcesParams $params, string $resourceType): string
    {
        $filters = [];

        if (!empty($params->country)) {
            $countries = explode(',', $params->country);
            $countryFilters = array_map(function($country) use ($resourceType) {
                $trimmedCountry = trim($country);
                // Formato correto: country:"valor"
                if ($resourceType === 'webResources') {
                    return 'publication_country:"' . $trimmedCountry . '"';
                } else {
                    return 'publication_country:"' . $trimmedCountry . '"';
                }
            }, $countries);
            // Para múltiplos países, usar OR sem parênteses
            $filters[] = implode(' OR ', $countryFilters);
        }

        if (!empty($params->subject)) {
            // Formato correto: subject:"valor"
            $filters[] = 'subject:"' . trim($params->subject) . '"';
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