<?php
namespace BV\Shortcodes;

use BV\API\BvsaludClient;
use BV\API\WebResourceDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

/**
 * Shortcode [bvs_web_resources] para exibir recursos web da BVS
 * Funciona exatamente como BvsJournalsShortcode mas para recursos LIS
 */
final class BvsWebResourcesShortcode {
    
    public function __construct() {
        add_shortcode('bvs_web_resources', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    public function render($atts, $content = ''): string {
        $atts = shortcode_atts([
            'country' => '',
            'subject' => '',
            'term' => '',
            'type' => '',
            'searchTitle' => '',
            'count' => 12,
            'max' => 50,
            'show_pagination' => 'false',
            'page' => 1,
            'template' => 'default',
            'show_fields' => 'title,type,country',
            'columns' => 4,
            'showFilters' => 'false',
            'showfilters' => 'false',
        ], $atts, 'bvs_web_resources');
        
        // Mapear parâmetros da URL para atributos
        $urlParams = [
            'bvsCountry' => 'country',
            'bvsSubject' => 'subject',
            'bvsTerm' => 'term',
            'bvsType' => 'type',
            'bvsSearchTitle' => 'searchTitle',
            'bvsTitle' => 'searchTitle',
            'bvsCount' => 'count',
            'bvsTemplate' => 'template',
            'bvsColumns' => 'columns',
        ];
        
        foreach ($urlParams as $urlKey => $attKey) {
            if (isset($_GET[$urlKey]) && !empty($_GET[$urlKey])) {
                $atts[$attKey] = sanitize_text_field($_GET[$urlKey]);
            }
        }
        
        // Processar checkboxes de países
        if (isset($_GET['bvsCountries']) && is_array($_GET['bvsCountries'])) {
            $selectedCountries = array_map('sanitize_text_field', $_GET['bvsCountries']);
            $atts['country'] = implode(',', $selectedCountries);
        }
        
        // Sanitizar atributos
        $atts['count'] = max(1, min(100, (int) $atts['count']));
        $atts['max'] = max(1, min(1000, (int) $atts['max']));
        $atts['page'] = max(1, (int) $atts['page']);
        $atts['columns'] = max(1, min(6, (int) $atts['columns']));
        
        // Converter showFilters para boolean
        $showFilters = filter_var($atts['showFilters'], FILTER_VALIDATE_BOOLEAN) || 
                      filter_var($atts['showfilters'], FILTER_VALIDATE_BOOLEAN);
        
        try {
            $client = new BvsaludClient();
            
            $country = trim($atts['country']);
            $subject = trim($atts['subject']);
            $term = trim($atts['term']);
            $type = trim($atts['type']);
            $searchTitle = trim($atts['searchTitle']);
            
            $resources = [];
            $totalResources = 0;
            $filterQuery = '';
            $queryParts = [];
            
            if (!empty($searchTitle)) {
                $queryParts[] = 'title:"' . $searchTitle . '"';
            }
            
            if (!empty($term)) {
                $queryParts[] = $term;
            }
            
            if (!empty($type)) {
                $queryParts[] = 'type:"' . $type . '"';
            }
            
            if (!empty($subject)) {
                $queryParts[] = 'subject_area:"' . $subject . '"';
            }
            
            $hasCountry = !empty($country);
            if ($hasCountry && !empty($queryParts)) {
                $countryFilter = $this->buildCountryFilter($country);
                $filterQuery = 'publication_country:' . $countryFilter;
            }
            
            $finalQuery = !empty($queryParts) ? implode(' AND ', $queryParts) : '*:*';
            
            if ($hasCountry && empty($queryParts)) {
                if (!$atts['show_pagination']) {
                    $firstCall = $client->getWebResourcesByCountry($country, 1);
                    $totalResources = $firstCall['total'] ?? 0;
                    $results = $client->getWebResourcesByCountry(
                        $country, 
                        min($totalResources, $atts['max'])
                    );
                } else {
                    $start = ($atts['page'] - 1) * $atts['count'];
                    $results = $client->getWebResourcesByCountry(
                        $country, 
                        $atts['count']
                    );
                    $totalResources = $results['total'] ?? 0;
                }
                $resources = $results['resources'] ?? [];
            } else {
                $searchParams = [
                    'q' => $finalQuery,
                    'count' => $atts['count'],
                    'start' => ($atts['page'] - 1) * $atts['count']
                ];
                
                if (!empty($filterQuery)) {
                    $searchParams['fq'] = $filterQuery;
                }
                
                if (!$atts['show_pagination']) {
                    $firstCall = $client->searchWebResources(array_merge($searchParams, ['count' => 1, 'start' => 0]));
                    $totalResources = $firstCall['total'] ?? 0;
                    
                    $searchParams['count'] = min($totalResources, $atts['max']);
                    $searchParams['start'] = 0;
                    $results = $client->searchWebResources($searchParams);
                } else {
                    $results = $client->searchWebResources($searchParams);
                    $totalResources = $results['total'] ?? 0;
                }
                $rawResources = $results['resources'] ?? [];
                $resources = array_filter(
                    array_map(function($resource) {
                        if ($resource instanceof WebResourceDto) {
                            return $resource;
                        }
                        $dto = new WebResourceDto($resource);
                        return $dto->isValid() ? $dto : null;
                    }, $rawResources),
                    function($r) { return $r !== null; }
                );
            }
            
            $content = $this->renderResources($resources, $atts, $totalResources);
            
            // Se showFilters está ativo, renderizar com sidebar
            if ($showFilters) {
                return $this->renderWithFilters($content, $atts);
            }
            
            return $content;
            
        } catch (\Exception $e) {
            return '<div class="bvs-error">Erro ao buscar recursos: ' . esc_html($e->getMessage()) . '</div>';
        }
    }
    
    /**
     * Renderiza layout com sidebar de filtros
     */
    private function renderWithFilters(string $content, array $atts): string {
        $filtersSidebar = $this->renderFiltersSidebar($atts);
        
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
    private function renderFiltersSidebar(array $atts): string {
        $currentTitle = isset($_GET['bvsTitle']) ? sanitize_text_field($_GET['bvsTitle']) : '';
        $currentTerm = isset($_GET['bvsTerm']) ? sanitize_text_field($_GET['bvsTerm']) : '';
        $currentCountry = isset($_GET['bvsCountry']) ? sanitize_text_field($_GET['bvsCountry']) : '';
        $currentType = isset($_GET['bvsType']) ? sanitize_text_field($_GET['bvsType']) : '';
        $currentSubject = isset($_GET['bvsSubject']) ? sanitize_text_field($_GET['bvsSubject']) : '';
        
        ob_start();
        ?>
        <div class="bvs-filters-box">
            <h3 class="bvs-filters-title">Filtros</h3>
            
            <form method="get" action="">
                <?php
                // Preservar parâmetros existentes da URL
                foreach ($_GET as $key => $value) {
                    if (!in_array($key, ['bvsTitle', 'bvsTerm', 'bvsCountry', 'bvsType', 'bvsSubject', 'bvsCountries'])) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                ?>
                
                <!-- Filtro de Título -->
                <div class="bvs-filter-group">
                    <label class="bvs-filter-label" for="bvsTitle">Título:</label>
                    <input 
                        type="text" 
                        id="bvsTitle" 
                        name="bvsTitle" 
                        class="bvs-filter-input" 
                        value="<?php echo esc_attr($currentTitle); ?>"
                        placeholder="Digite o título..."
                    >
                </div>
                
                <!-- Filtro de Termo -->
                <div class="bvs-filter-group">
                    <label class="bvs-filter-label" for="bvsTerm">Busca livre:</label>
                    <input 
                        type="text" 
                        id="bvsTerm" 
                        name="bvsTerm" 
                        class="bvs-filter-input" 
                        value="<?php echo esc_attr($currentTerm); ?>"
                        placeholder="Digite o termo..."
                    >
                </div>
                
                <!-- Filtros de País -->
                <div class="bvs-filter-group">
                    <label class="bvs-filter-label">Países:</label>
                    
                    <?php
                    // Obter países disponíveis da API
                    $client = new BvsaludClient();
                    $availableCountries = $client->getAvailableCountries();
                    $selectedCountries = !empty($currentCountry) ? explode(',', $currentCountry) : [];
                    ?>
                    
                    <div class="bvs-checkbox-container">
                        <?php
                        if (!empty($availableCountries)) {
                            foreach ($availableCountries as $country) {
                                $countryName = $country['name'];
                                $countryCount = $country['count'];
                                $isChecked = in_array($countryName, $selectedCountries);
                                ?>
                                <label class="bvs-checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        name="bvsCountries[]" 
                                        value="<?php echo esc_attr($countryName); ?>"
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
                    <button type="submit" class="bvs-btn-filter bvs-btn-primary">Buscar</button>
                    <a 
                        href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" 
                        class="bvs-btn-filter bvs-btn-secondary"
                    >
                        Limpar
                    </a>
                </div>
                
                <!-- Filtros ativos -->
                <?php if (!empty($currentTitle) || !empty($currentTerm) || !empty($currentCountry) || !empty($currentType) || !empty($currentSubject)): ?>
                    <div class="bvs-active-filters">
                        <strong>Filtros ativos:</strong>
                        <?php if (!empty($currentTitle)): ?>
                            <span class="bvs-filter-tag">
                                Título: <?php echo esc_html($currentTitle); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsTitle')); ?>" class="bvs-remove-filter">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($currentTerm)): ?>
                            <span class="bvs-filter-tag">
                                Termo: <?php echo esc_html($currentTerm); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsTerm')); ?>" class="bvs-remove-filter">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($currentCountry)): ?>
                            <span class="bvs-filter-tag">
                                País: <?php echo esc_html($currentCountry); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsCountry')); ?>" class="bvs-remove-filter">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($currentType)): ?>
                            <span class="bvs-filter-tag">
                                Tipo: <?php echo esc_html($currentType); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsType')); ?>" class="bvs-remove-filter">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($currentSubject)): ?>
                            <span class="bvs-filter-tag">
                                Assunto: <?php echo esc_html($currentSubject); ?>
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
     * Constrói filtro de país no formato da API BVS (case-insensitive)
     * Suporta múltiplos países separados por vírgula
     */
    private function buildCountryFilter(string $country): string {
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
    
    private function renderResources(array $resources, array $atts, int $total): string {
        $showFields = array_map('trim', explode(',', $atts['show_fields']));
        
        // Sempre usa o sistema genérico de grid
        return $this->renderGenericGrid($resources, $atts, $total, $showFields);
    }
    
    /**
     * Renderiza usando o sistema genérico de grid
     */
    private function renderGenericGrid(array $resources, array $atts, int $total, array $showFields): string {
        // Converte WebResourceDto[] para ResourceCardDto[]
        $cards = array_map(function($resource) use ($showFields) {
            return $this->convertResourceToCard($resource, $showFields);
        }, $resources);
        
        // Remove recursos inválidos
        $cards = array_filter($cards, function($card) {
            return $card->isValid();
        });
        
        // Carrega o template genérico
        $templatePath = trailingslashit(dirname(__DIR__, 1)) . 'Templates/bvs-grid.php';
        
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        
        return $this->renderFallback($resources, $atts, $total);
    }
    
    /**
     * Converte WebResourceDto para ResourceCardDto
     */
    private function convertResourceToCard(WebResourceDto $resource, array $showFields): ResourceCardDto {
        $card = new ResourceCardDto();
        
        // Título
        $card->title = $resource->title ?? 'Sem título';
        
        // URL
        $card->url = $resource->url ?? '#';
        
        // Tipo
        if (in_array('type', $showFields)) {
            $card->addField('Tipo', $resource->getFormattedType());
        }
        
        // País
        if (in_array('country', $showFields) && $resource->country) {
            $card->addField('País', $resource->country);
        }
        
        // Descrição
        if (in_array('description', $showFields) && $resource->description) {
            $card->addField('Descrição', $resource->description);
        }
        
        // Instituição
        if (in_array('institution', $showFields) && $resource->institution) {
            $card->addField('Instituição', $resource->institution);
        }
        
        // Idioma
        if (in_array('language', $showFields)) {
            $languages = $resource->getLanguagesString();
            if ($languages) {
                $card->addField('Idioma', $languages);
            }
        }
        
        // Assunto
        if (in_array('subject', $showFields) && $resource->subject_area) {
            $card->addField('Assunto', $resource->subject_area);
        }
        
        return $card;
    }
    
    /**
     * Renderização de fallback
     */
    private function renderFallback(array $resources, array $atts, int $total): string {
        if (empty($resources)) {
            return '<div class="bvs-resources-container"><p>Nenhum recurso encontrado.</p></div>';
        }
        
        ob_start();
        ?>
        <div class="bvs-resources-container">
            <div class="bvs-resources-header">
                <p class="bvs-resources-count"><?php echo $total; ?> recursos encontrados</p>
            </div>
            <div class="bvs-resources-list">
                <?php foreach ($resources as $resource): ?>
                    <div class="bvs-resource-item">
                        <h3 class="resource-title">
                            <a href="<?php echo esc_url($resource->url ?? '#'); ?>" target="_blank">
                                <?php echo esc_html($resource->title ?? 'Sem título'); ?>
                            </a>
                        </h3>
                        <?php if ($resource->description): ?>
                            <p><?php echo esc_html($resource->description); ?></p>
                        <?php endif; ?>
                        <div class="resource-meta">
                            <?php if ($resource->type): ?>
                                <span><strong>Tipo:</strong> <?php echo esc_html($resource->getFormattedType()); ?></span>
                            <?php endif; ?>
                            <?php if ($resource->country): ?>
                                <span><strong>País:</strong> <?php echo esc_html($resource->country); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function enqueueAssets(): void {
        // O CSS público já é carregado pelo Plugin.php
    }
}
