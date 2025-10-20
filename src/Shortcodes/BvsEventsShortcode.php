<?php
namespace BV\Shortcodes;

use BV\API\BvsaludClient;
use BV\API\EventDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

/**
 * Shortcode [bvs_events] para exibir eventos da BVS
 * Funciona exatamente como BvsJournalsShortcode e BvsWebResourcesShortcode
 */
final class BvsEventsShortcode {

    public function register(): void {
        add_shortcode('bvs_events', [$this, 'render']);
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
            'show_fields' => 'title,type,country,period',
            'showFilters' => 'false',
            'showfilters' => 'false',
        ], $atts, 'bvs_events');

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
        $atts['showFilters'] = $atts['showFilters'] === 'true';
        
        $client = new BvsaludClient(\BV\Admin\SettingsPage::getEventsUrl());
        $events = [];
        $totalEvents = 0;
        $error = null;
        $results = [];
        
        try {
            $connectionTest = $client->testEventsConnection();
            if (!$connectionTest['success']) {
                return $this->renderError('Erro de conexão com a API BVS: ' . $connectionTest['message']);
            }
            
            $searchTitle = !empty($atts['searchTitle']) ? trim($atts['searchTitle']) : '';
            $search = !empty($atts['search']) ? trim($atts['search']) : '';
            $subject = !empty($atts['subject']) ? trim($atts['subject']) : '';
            $country = !empty($atts['country']) ? trim($atts['country']) : '';
            $type = !empty($atts['type']) ? trim($atts['type']) : '';
            
            $queryParts = [];
            $filterQuery = '';
            
            if (!empty($searchTitle)) {
                $queryParts[] = 'title:"' . $searchTitle . '"';
            }
            
            if (!empty($search)) {
                $queryParts[] = $search;
            }
            
            if (!empty($type)) {
                // Se houver múltiplos tipos separados por vírgula, criar query com OR
                if (strpos($type, ',') !== false) {
                    $types = array_map('trim', explode(',', $type));
                    $typeQueries = [];
                    foreach ($types as $singleType) {
                        $typeQueries[] = 'event_type:"' . $singleType . '"';
                    }
                    $queryParts[] = '(' . implode(' OR ', $typeQueries) . ')';
                } else {
                    $queryParts[] = 'event_type:"' . $type . '"';
                }
            }
            
            if (!empty($subject)) {
                $queryParts[] = 'descriptor:"' . $subject . '"';
            }
            
            $hasCountry = !empty($country);
            if ($hasCountry && !empty($queryParts)) {
                $countryFilter = $this->buildCountryFilter($country);
                $filterQuery = 'country:' . $countryFilter;
            }
            
            $finalQuery = !empty($queryParts) ? implode(' AND ', $queryParts) : '*:*';
            
            if ($hasCountry && empty($queryParts)) {
                if (!$atts['show_pagination']) {
                    $firstCall = $client->getEventsByCountry($country, 1);
                    $totalEvents = $firstCall['total'] ?? 0;
                    $results = $client->getEventsByCountry(
                        $country, 
                        min($totalEvents, $atts['max'])
                    );
                } else {
                    $results = $client->getEventsByCountry(
                        $country, 
                        $atts['limit']
                    );
                    $totalEvents = $results['total'] ?? 0;
                }
                $events = $results['events'] ?? [];
            } else {
                $searchParams = [
                    'q' => $finalQuery,
                    'count' => $atts['limit'],
                    'start' => ($atts['page'] - 1) * $atts['limit']
                ];
                
                if (!empty($filterQuery)) {
                    $searchParams['fq'] = $filterQuery;
                }
                
                if (!$atts['show_pagination']) {
                    $firstCall = $client->searchEvents(array_merge($searchParams, ['count' => 1, 'start' => 0]));
                    $totalEvents = $firstCall['total'] ?? 0;
                    
                    $searchParams['count'] = min($totalEvents, $atts['max']);
                    $searchParams['start'] = 0;
                    $results = $client->searchEvents($searchParams);
                } else {
                    $results = $client->searchEvents($searchParams);
                    $totalEvents = $results['total'] ?? 0;
                }
                
                // Converter arrays para DTOs
                $rawEvents = $results['events'] ?? [];
                $events = array_filter(
                    array_map(function($event) {
                        if ($event instanceof EventDto) {
                            return $event;
                        }
                        $dto = new EventDto($event);
                        return $dto->isValid() ? $dto : null;
                    }, $rawEvents)
                );
            }
            
        } catch (\Exception $e) {
            return $this->renderError('Erro ao buscar eventos: ' . $e->getMessage());
        }
        
        if (empty($events)) {
            $content = $this->renderEmpty();
        } else {
            $content = $this->renderEvents($events, $atts, $totalEvents);
        }
        
        // Converte string para boolean (aceita tanto showFilters quanto showfilters)
        $showFiltersValue = !empty($atts['showFilters']) ? $atts['showFilters'] : $atts['showfilters'];
        $showFilters = filter_var($showFiltersValue, FILTER_VALIDATE_BOOLEAN);
            
        // Se showFilters = true, renderiza com sidebar de filtros
        if ($showFilters) {
            return $this->renderWithFilters($content, $atts);
        }
            
        return $content;
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
        // Pegar valores atuais dos filtros (da URL ou do shortcode)
        $currentTitle = $_GET['bvsTitle'] ?? $_GET['bvsSearchTitle'] ?? $atts['searchTitle'] ?? '';
        $currentCountry = $_GET['bvsCountry'] ?? $atts['country'] ?? '';
        $currentSubject = $_GET['bvsSubject'] ?? $atts['subject'] ?? '';
        $currentType = $_GET['bvsType'] ?? $atts['type'] ?? '';
        
        // Processar tipos selecionados para checkbox
        $selectedTypes = [];
        if (!empty($currentType)) {
            if (strpos($currentType, ',') !== false) {
                $selectedTypes = array_map('trim', explode(',', $currentType));
            } else {
                $selectedTypes = [$currentType];
            }
        }
        
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
                    $client = new BvsaludClient(\BV\Admin\SettingsPage::getEventsUrl());
                    $availableCountries = $client->getAvailableEventCountries();
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
                
                <!-- Filtros de Tipo de Evento -->
                <div class="bvs-filter-group">
                    <label class="bvs-filter-label">Tipo de Evento:</label>
                    
                    <?php
                    // Obter tipos de eventos disponíveis da API
                    $availableEventTypes = $client->getAvailableEventTypes();
                    ?>
                    
                    <div class="bvs-checkbox-container">
                        <?php
                        if (!empty($availableEventTypes)) {
                            foreach ($availableEventTypes as $eventType) {
                                $typeName = $eventType['name'];
                                $typeCount = $eventType['count'];
                                $isChecked = in_array($typeName, $selectedTypes);
                                ?>
                                <label class="bvs-checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        name="bvsTypes[]" 
                                        value="<?php echo esc_attr($typeName); ?>"
                                        <?php echo $isChecked ? 'checked' : ''; ?>
                                        class="bvs-checkbox"
                                    >
                                    <span class="bvs-checkbox-label">
                                        <?php echo esc_html($typeName); ?>
                                        <small class="bvs-count">(<?php echo $typeCount; ?>)</small>
                                    </span>
                                </label>
                                <?php
                            }
                        } else {
                            ?>
                            <div class="bvs-no-types">
                                <p>⚠️ Tipos de eventos não disponíveis</p>
                                <small>Verifique se a URL da API de eventos está configurada corretamente nas configurações do plugin.</small>
                            </div>
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
                <?php if (!empty($currentTitle) || !empty($currentSubject) || !empty($currentCountry) || !empty($currentType)): ?>
                    <div class="bvs-active-filters">
                        <strong>Filtros ativos:</strong>
                        <?php if (!empty($currentTitle)): ?>
                            <span class="bvs-filter-tag">
                                Título: <?php echo esc_html($currentTitle); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsTitle')); ?>" class="bvs-remove-filter">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($currentSubject)): ?>
                            <span class="bvs-filter-tag">
                                Assunto: <?php echo esc_html($currentSubject); ?>
                                <a href="<?php echo esc_url(remove_query_arg('bvsSubject')); ?>" class="bvs-remove-filter">×</a>
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
                                <a href="<?php echo esc_url(remove_query_arg(['bvsType', 'bvsTypes'])); ?>" class="bvs-remove-filter">×</a>
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
    
    private function renderEvents(array $events, array $atts, int $total): string {
        $showFields = array_map('trim', explode(',', $atts['show_fields']));
        
        // Sempre usa o sistema genérico de grid
        return $this->renderGenericGrid($events, $atts, $total, $showFields);
    }
    
    /**
     * Renderiza usando o sistema genérico de grid
     */
    private function renderGenericGrid(array $events, array $atts, int $total, array $showFields): string {
        // Converte EventDto[] para ResourceCardDto[]
        $cards = array_map(function($event) use ($showFields) {
            return $this->convertEventToCard($event, $showFields);
        }, $events);
        
        // Remove eventos inválidos
        $cards = array_filter($cards, function($card) {
            return $card->isValid();
        });
        
        // Carrega o template genérico
        $templatePath = trailingslashit(dirname(__DIR__, 1)) . 'Templates/bvs-grid.php';
        
        if (file_exists($templatePath)) {
            ob_start();
            // Passa os cards convertidos para o template
            $resources = $cards;
            include $templatePath;
            return ob_get_clean();
        }
        
        return $this->renderFallback($events, $atts, $total);
    }
    
    /**
     * Converte EventDto para ResourceCardDto
     */
    private function convertEventToCard(EventDto $event, array $showFields): ResourceCardDto {
        // 1. TÍTULO
        $title = '';
        if (in_array('title', $showFields) && $event->title) {
            $titleText = strlen($event->title) > 60 ? substr($event->title, 0, 57) . '...' : $event->title;
            if ($event->url) {
                $title = '<a href="' . esc_url($event->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = esc_html($titleText);
            }
        }
        
        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>
        
        <?php if (!empty($event->description)): ?>
            <div class="bvs-field">
                <p class="bvs-abstract"><?= esc_html($this->truncateText($event->description, 120)) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($event->city): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Local:</span> 
                <span class="bvs-field-value"><?= esc_html($event->city) ?></span>
                <?php if ($event->country): ?>
                    <span class="bvs-field-value"> - <?= esc_html($event->getFormattedCountry()) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (in_array('type', $showFields) && $event->getFormattedType()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Tipo:</span> 
                <span class="bvs-field-value"><?= esc_html($event->getFormattedType()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (in_array('period', $showFields) && $event->getFormattedPeriod()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Período:</span> 
                <span class="bvs-field-value"><?= esc_html($event->getFormattedPeriod()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($event->getFormattedModality()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Modalidade:</span> 
                <span class="bvs-field-value"><?= esc_html($event->getFormattedModality()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (in_array('language', $showFields) && $event->getLanguagesString()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Idioma:</span> 
                <span class="bvs-field-value"><?= esc_html($event->getLanguagesString()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($event->contact_email): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Contato:</span> 
                <span class="bvs-field-value"><?= esc_html($event->contact_email) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (in_array('keywords', $showFields) && $event->getKeywordsString()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Palavras-chave:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($event->getKeywordsString(), 50)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (in_array('country', $showFields) && $event->getFormattedCountry()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">País:</span> 
                <span class="bvs-field-value"><?= esc_html($event->getFormattedCountry()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($event->getTargetGroupsString()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Público-alvo:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($event->getTargetGroupsString(), 80)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($event->getFormattedCreatedDate() || $event->getFormattedUpdatedDate()): ?>
            <div class="bvs-dates">
                <?php if ($event->getFormattedCreatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Criado:</span> 
                        <span class="bvs-date-value"><?= esc_html($event->getFormattedCreatedDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($event->getFormattedUpdatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Atualizado:</span> 
                        <span class="bvs-date-value"><?= esc_html($event->getFormattedUpdatedDate()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $content = ob_get_clean();
        
        // 3. TAGS
        $tags = [];
        if ($event->getFormattedSubjectArea()) {
            $tags[] = $event->getFormattedSubjectArea();
        }
        if ($event->getFormattedCountry()) {
            $tags[] = $event->getFormattedCountry();
        }
        if ($event->getFormattedType()) {
            $tags[] = $event->getFormattedType();
        }
        
        // 4. LINK
        $link = $event->url ?? '';
        
        // Cria o ResourceCardDto
        return new ResourceCardDto([
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'tags' => $tags,
        ]);
    }
    
    /**
     * Renderização de fallback
     */
    private function renderFallback(array $events, array $atts, int $total): string {
        if (empty($events)) {
            return '<div class="bvs-events-container"><p>Nenhum evento encontrado.</p></div>';
        }
        
        ob_start();
        ?>
        <div class="bvs-events-container">
            <div class="bvs-events-header">
                <p class="bvs-events-count"><?php echo $total; ?> eventos encontrados</p>
            </div>
            <div class="bvs-events-list">
                <?php foreach ($events as $event): ?>
                    <div class="bvs-event-item">
                        <h3 class="event-title">
                            <a href="<?php echo esc_url($event->url ?? '#'); ?>" target="_blank">
                                <?php echo esc_html($event->title ?? 'Sem título'); ?>
                            </a>
                        </h3>
                        <?php if ($event->description): ?>
                            <p><?php echo esc_html($event->description); ?></p>
                        <?php endif; ?>
                        <div class="event-meta">
                            <?php if ($event->type): ?>
                                <span><strong>Tipo:</strong> <?php echo esc_html($event->getFormattedType()); ?></span>
                    <?php endif; ?>
                            <?php if ($event->city): ?>
                                <span><strong>Local:</strong> <?php echo esc_html($event->city); ?></span>
                    <?php endif; ?>
                            <?php if ($event->country): ?>
                                <span><strong>País:</strong> <?php echo esc_html($event->getFormattedCountry()); ?></span>
                    <?php endif; ?>
                            <?php if ($event->getFormattedPeriod()): ?>
                                <span><strong>Período:</strong> <?php echo esc_html($event->getFormattedPeriod()); ?></span>
                    <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function renderError(string $message): string {
        return '<div class="bvs-error"><p><strong>Erro:</strong> ' . esc_html($message) . '</p></div>';
    }
    
    private function renderEmpty(): string {
        return '<div class="bvs-empty"><p>' . esc_html__('Nenhum evento encontrado.', 'bvsalud-integrator') . '</p></div>';
    }
    
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
}