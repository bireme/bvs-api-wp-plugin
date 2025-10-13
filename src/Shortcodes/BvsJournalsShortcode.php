<?php
namespace BV\Shortcodes;

use BV\API\BvsaludClient;
use BV\API\JournalDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH')) exit;

/**
 * Shortcode para exibir journals da API BVS Saúde
 * 
 * Exemplos de uso:
 * [bvs_journals country="Brasil" max="20"] - Grid 4 colunas com até 20 journals do Brasil
 * [bvs_journals country="Argentina" max="12" template="grid"] - Grid personalizado
 * [bvs_journals subject="medicina" limit="10"] - Lista por assunto
 * [bvs_journals search="cardiologia" limit="5" template="compact"] - Busca compacta
 * [bvs_journals searchTitle="saúde pública" limit="10"] - Busca por título
 */
final class BvsJournalsShortcode {
    
    public function register(): void {
        add_shortcode('bvs_journals', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    public function render($atts, $content = ''): string {
        $atts = shortcode_atts([
            'country' => '',
            'subject' => '',
            'search' => '',
            'searchTitle' => '',
            'issn' => '',
            'limit' => 12,
            'max' => 50, // quantidade máxima de journals a exibir
            'show_pagination' => 'false',
            'page' => 1,
            'template' => 'default', // default, compact, detailed, grid
            'show_fields' => 'title,issn,publisher,country', // campos a exibir
            'columns' => 4, // para template grid
            'showFilters' => 'false', // Mostrar barra lateral de filtros
            'showfilters' => 'false', // Mostrar barra lateral de filtros (minúsculo)
        ], $atts, 'bvs_journals');
        
        // Parâmetros da URL sobrescrevem os do shortcode
        $urlParams = [
            'bvsCountry' => 'country',
            'bvsSubject' => 'subject',
            'bvsSearchTitle' => 'searchTitle',
            'bvsTitle' => 'searchTitle', // Alias para searchTitle
            'bvsIssn' => 'issn',
            'bvsLimit' => 'limit',
            'bvsMax' => 'max',
            'bvsTemplate' => 'template',
            'bvsColumns' => 'columns',
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
        
        $atts['limit'] = max(1, min(100, (int) $atts['limit']));
        $atts['max'] = max(1, min(500, (int) $atts['max']));
        $atts['page'] = max(1, (int) $atts['page']);
        $atts['show_pagination'] = $atts['show_pagination'] === 'true';
        $atts['showFilters'] = $atts['showFilters'] === 'true';
        
        if (!empty($atts['country']) && $atts['template'] === 'default') {
            $atts['template'] = 'grid';
        }
        
        $client = new BvsaludClient();
        $journals = [];
        $totalJournals = 0;
        $error = null;
        $results = [];
        
        try {
            $connectionTest = $client->testConnection();
            if (!$connectionTest['success']) {
                return $this->renderError('Erro de conexão com a API BVS: ' . $connectionTest['message']);
            }
            
            if (!empty($atts['issn'])) {
                $journal = $client->getJournalByIssn(sanitize_text_field($atts['issn']));
                $journals = $journal && $journal->isValid() ? [$journal] : [];
                $totalJournals = count($journals);
                } else {
                $searchTitle = !empty($atts['searchTitle']) ? trim($atts['searchTitle']) : '';
                $search = !empty($atts['search']) ? trim($atts['search']) : '';
                $subject = !empty($atts['subject']) ? trim($atts['subject']) : '';
                $country = !empty($atts['country']) ? trim($atts['country']) : '';
                
                $queryParts = [];
                $filterQuery = '';
                
                if (!empty($searchTitle)) {
                    $queryParts[] = 'title:"' . $searchTitle . '"';
                }
                
                if (!empty($search)) {
                    $queryParts[] = $search;
                }
                
                if (!empty($subject)) {
                    $queryParts[] = 'subject_area:"' . $subject . '"';
                }
                
                $hasCountry = !empty($country);
                if ($hasCountry && !empty($queryParts)) {
                    $countryFilter = $this->buildCountryFilter($country);
                    $filterQuery = 'country:' . $countryFilter;
                }
                
                $finalQuery = !empty($queryParts) ? implode(' AND ', $queryParts) : '*:*';
                
                if ($hasCountry && empty($queryParts)) {
                if (!$atts['show_pagination']) {
                    $firstCall = $client->getJournalsByCountry($country, 1);
                    $totalJournals = $firstCall['total'] ?? 0;
                    $results = $client->getJournalsByCountry(
                        $country, 
                        min($totalJournals, $atts['max'])
                    );
                } else {
                    $start = ($atts['page'] - 1) * $atts['limit'];
                    $results = $client->getJournalsByCountry(
                            $country, 
                        $atts['limit'],
                        $start
                    );
                    $totalJournals = $results['total'] ?? 0;
                }
                $journals = $results['journals'] ?? [];
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
                    $firstCall = $client->searchJournals(array_merge($searchParams, ['count' => 1, 'start' => 0]));
                    $totalJournals = $firstCall['total'] ?? 0;
                    
                    $searchParams['count'] = min($totalJournals, $atts['max']);
                    $searchParams['start'] = 0;
                    $results = $client->searchJournals($searchParams);
                } else {
                    $results = $client->searchJournals($searchParams);
                    $totalJournals = $results['total'] ?? 0;
                }
                    
                    // Converter arrays para DTOs
                    $rawJournals = $results['journals'] ?? [];
                    $journals = array_filter(
                        array_map(function($journal) {
                            if ($journal instanceof JournalDto) {
                                return $journal;
                            }
                            $dto = new JournalDto($journal);
                            return $dto->isValid() ? $dto : null;
                        }, $rawJournals)
                    );
                }
            }
            
        } catch (Exception $e) {
            return $this->renderError('Erro ao buscar journals: ' . $e->getMessage());
        }
        
        if (empty($journals)) {
            $content = $this->renderEmpty();
        } else {
            $content = $this->renderJournals($journals, $atts, $totalJournals);
        }
        
        // Converte string para boolean (aceita tanto showFilters quanto showfilters)
        $showFiltersValue = !empty($atts['showFilters']) ? $atts['showFilters'] : $atts['showfilters'];
        $showFilters = filter_var($showFiltersValue, FILTER_VALIDATE_BOOLEAN);
        
        // Se showFilters = true, renderiza com sidebar de filtros
        if ($showFilters) {
            return $this->renderWithFilters($content, $atts);
        }
        
        // Adiciona CSS inline diretamente no HTML
        $css = '<style>' . $this->getInlineCSS() . '</style>';
        
        return $css . $content;
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
    
    
    private function renderJournals(array $journals, array $atts, int $total): string {
        $showFields = array_map('trim', explode(',', $atts['show_fields']));
        
        // Sempre usa o sistema genérico de grid
        return $this->renderGenericGrid($journals, $atts, $total, $showFields);
    }
    
    /**
     * Renderiza usando o sistema genérico de grid
     */
    private function renderGenericGrid(array $journals, array $atts, int $total, array $showFields): string {
        // Converte JournalDto[] para ResourceCardDto[]
        $resources = array_map(function($journal) use ($showFields) {
            return $this->convertJournalToResourceCard($journal, $showFields);
        }, $journals);
        
        // Remove recursos inválidos
        $resources = array_filter($resources, function($resource) {
            return $resource->isValid();
        });
        
        // Carrega o template genérico
        $templatePath = trailingslashit(dirname(__DIR__, 1)) . 'Templates/bvs-grid.php';
        
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        
        return $this->renderFallback($journals, $atts, $total);
    }
    
    /**
     * Converte JournalDto para ResourceCardDto
     * 
     * Aplica todas as regras de negócio e formatação específicas de journals
     */
    private function convertJournalToResourceCard(JournalDto $journal, array $showFields): ResourceCardDto {
        // 1. TÍTULO
        $title = '';
        if (in_array('title', $showFields) && $journal->title) {
            $titleText = strlen($journal->title) > 60 ? substr($journal->title, 0, 57) . '...' : $journal->title;
            if ($journal->url) {
                $title = '<a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = esc_html($titleText);
            }
        }
        
        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>
        
        <?php if ($journal->getFormattedSubjectArea()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Descritor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($journal->getFormattedSubjectArea(), 50)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($journal->responsibility_mention): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Responsabilidade:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($journal->responsibility_mention, 45)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (in_array('publisher', $showFields) && $journal->publisher): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Editor:</span> 
                <span class="bvs-field-value"><?= esc_html($this->truncateText($journal->publisher, 40)) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($journal->getPrimaryIssn()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">ISSN:</span> 
                <span class="bvs-field-value"><?= esc_html($journal->getPrimaryIssn()) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($journal->initial_date || $journal->created_date || $journal->updated_date): ?>
            <div class="bvs-dates">
                <?php if ($journal->getFormattedInitialDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Início:</span> 
                        <span class="bvs-date-value"><?= esc_html($journal->getFormattedInitialDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($journal->getFormattedCreatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Criado:</span> 
                        <span class="bvs-date-value"><?= esc_html($journal->getFormattedCreatedDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($journal->getFormattedUpdatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Atualizado:</span> 
                        <span class="bvs-date-value"><?= esc_html($journal->getFormattedUpdatedDate()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $content = ob_get_clean();
        
        // 3. TAGS
        $tags = [];
        if ($journal->getFormattedSubjectArea()) {
            $tags[] = $journal->getFormattedSubjectArea();
        }
        if ($journal->getFormattedCountry()) {
            $tags[] = $journal->getFormattedCountry();
        }
        
        // 4. LINK
        $link = $journal->url ?? '';
        
        // Cria o ResourceCardDto
        return new ResourceCardDto([
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'tags' => $tags,
        ]);
    }
    
    /**
     * Fallback para renderização inline caso os templates não existam
     */
    private function renderFallback(array $journals, array $atts, int $total): string {
        $showFields = array_map('trim', explode(',', $atts['show_fields']));
        $template = $atts['template'];
        
        $html = '<div class="bvs-journals-container" data-template="' . esc_attr($template) . '">';
        

        if ($total > 0) {
            $html .= '<div class="bvs-journals-header">';
            $html .= '<p class="bvs-journals-count">';
            $html .= sprintf(
                _n(
                    '%d journal encontrado',
                    '%d journals encontrados',
                    $total,
                    'bvsalud-integrator'
                ),
                $total
            );
            $html .= '</p>';
            $html .= '</div>';
        }
        

        $listClass = $template === 'grid' ? 'bvs-grid' : 'bvs-journals-list';
        $html .= '<div class="' . $listClass . '" data-columns="' . esc_attr($atts['columns']) . '">';
        
        foreach ($journals as $journal) {
            /** @var JournalDto $journal */
            if (!$journal->isValid()) continue;
            
            $html .= $this->renderJournalItem($journal, $showFields, $template);
        }
        
        $html .= '</div>';
        
        // Paginação
        if ($atts['show_pagination'] && $total > $atts['limit']) {
            $html .= $this->renderPagination($atts['page'], $atts['limit'], $total);
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderJournalItem(JournalDto $journal, array $showFields, string $template): string {
        $itemClass = $template === 'grid' ? 'bvs-item' : 'bvs-journal-item';
        $html = '<div class="' . $itemClass . '">';
        
        switch ($template) {
            case 'compact':
                $html .= $this->renderCompactItem($journal, $showFields);
                break;
            case 'detailed':
                $html .= $this->renderDetailedItem($journal, $showFields);
                break;
            case 'grid':
                $html .= $this->renderGridItem($journal, $showFields);
                break;
            default:
                $html .= $this->renderDefaultItem($journal, $showFields);
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderDefaultItem(JournalDto $journal, array $showFields): string {
        $html = '';
        
        if (in_array('title', $showFields) && $journal->title) {
            $html .= '<h4 class="journal-title">';
            if ($journal->url) {
                $html .= '<a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener">';
                $html .= esc_html($journal->title);
                $html .= '</a>';
            } else {
                $html .= esc_html($journal->title);
            }
            $html .= '</h4>';
        }
        
        $html .= '<div class="journal-meta">';
        
        if (in_array('issn', $showFields) && $journal->getPrimaryIssn()) {
            $html .= '<span class="journal-issn"><strong>ISSN:</strong> ' . esc_html($journal->getPrimaryIssn()) . '</span>';
        }
        
        if (in_array('publisher', $showFields) && $journal->publisher) {
            $html .= '<span class="journal-publisher"><strong>Editor:</strong> ' . esc_html($journal->publisher) . '</span>';
        }
        
        if (in_array('country', $showFields) && $journal->country) {
            $html .= '<span class="journal-country"><strong>País:</strong> ' . esc_html($journal->country) . '</span>';
        }
        
        if (in_array('languages', $showFields) && $journal->getLanguagesString()) {
            $html .= '<span class="journal-languages"><strong>Idiomas:</strong> ' . esc_html($journal->getLanguagesString()) . '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderCompactItem(JournalDto $journal, array $showFields): string {
        $html = '<div class="journal-compact">';
        
        if (in_array('title', $showFields) && $journal->title) {
            $html .= '<span class="journal-title">' . esc_html($journal->title) . '</span>';
        }
        
        $meta = [];
        if (in_array('issn', $showFields) && $journal->getPrimaryIssn()) {
            $meta[] = 'ISSN: ' . $journal->getPrimaryIssn();
        }
        if (in_array('country', $showFields) && $journal->country) {
            $meta[] = $journal->country;
        }
        
        if (!empty($meta)) {
            $html .= ' <small class="journal-meta">(' . implode(' | ', $meta) . ')</small>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderDetailedItem(JournalDto $journal, array $showFields): string {
        $html = '<div class="journal-detailed">';
        
        if (in_array('title', $showFields) && $journal->title) {
            $html .= '<h4 class="journal-title">' . esc_html($journal->title) . '</h4>';
        }
        
        $html .= '<div class="journal-details">';
        
        if (in_array('issn', $showFields)) {
            if ($journal->issn) {
                $html .= '<p><strong>ISSN:</strong> ' . esc_html($journal->issn) . '</p>';
            }
            if ($journal->eissn) {
                $html .= '<p><strong>eISSN:</strong> ' . esc_html($journal->eissn) . '</p>';
            }
        }
        
        if (in_array('publisher', $showFields) && $journal->publisher) {
            $html .= '<p><strong>Editor:</strong> ' . esc_html($journal->publisher) . '</p>';
        }
        
        if (in_array('country', $showFields) && $journal->country) {
            $html .= '<p><strong>País:</strong> ' . esc_html($journal->country) . '</p>';
        }
        
        if (in_array('languages', $showFields) && $journal->getLanguagesString()) {
            $html .= '<p><strong>Idiomas:</strong> ' . esc_html($journal->getLanguagesString()) . '</p>';
        }
        
        if ($journal->subject_area) {
            $html .= '<p><strong>Área:</strong> ' . esc_html($journal->subject_area) . '</p>';
        }
        
        if ($journal->url) {
            $html .= '<p><a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener" class="journal-link">Acessar Journal</a></p>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    private function renderGridItem(JournalDto $journal, array $showFields): string {
        $html = '<div class="bvs-item-content">';
        
        // Título
        if (in_array('title', $showFields) && $journal->title) {
            $html .= '<h4 class="bvs-item-title">';
            if ($journal->url) {
                $html .= '<a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener">';
                $html .= esc_html($this->truncateText($journal->title, 60));
                $html .= '</a>';
            } else {
                $html .= esc_html($this->truncateText($journal->title, 60));
            }
            $html .= '</h4>';
        }
        
        // Wrapper de informações
        $html .= '<div class="bvs-item-info">';
        
        // Descriptor (subject_area)
        if ($journal->getFormattedSubjectArea()) {
            $html .= '<div class="bvs-field">';
            $html .= '<span class="bvs-field-label">Descritor:</span> ';
            $html .= '<span class="bvs-field-value">' . esc_html($this->truncateText($journal->getFormattedSubjectArea(), 50)) . '</span>';
            $html .= '</div>';
        }
        
        // Responsibility Mention
        if ($journal->responsibility_mention) {
            $html .= '<div class="bvs-field">';
            $html .= '<span class="bvs-field-label">Responsabilidade:</span> ';
            $html .= '<span class="bvs-field-value">' . esc_html($this->truncateText($journal->responsibility_mention, 45)) . '</span>';
            $html .= '</div>';
        }
        
        // Publisher
        if (in_array('publisher', $showFields) && $journal->publisher) {
            $html .= '<div class="bvs-field">';
            $html .= '<span class="bvs-field-label">Editor:</span> ';
            $html .= '<span class="bvs-field-value">' . esc_html($this->truncateText($journal->publisher, 40)) . '</span>';
            $html .= '</div>';
        }
        
        // ISSN
        if ($journal->getPrimaryIssn()) {
            $html .= '<div class="bvs-field">';
            $html .= '<span class="bvs-field-label">ISSN:</span> ';
            $html .= '<span class="bvs-field-value">' . esc_html($journal->getPrimaryIssn()) . '</span>';
            $html .= '</div>';
        }
        
        // Dates
        if ($journal->initial_date || $journal->created_date || $journal->updated_date) {
            $html .= '<div class="bvs-dates">';
            
            if ($journal->getFormattedInitialDate()) {
                $html .= '<div class="bvs-date">';
                $html .= '<span class="bvs-date-label">Início:</span> ';
                $html .= '<span class="bvs-date-value">' . esc_html($journal->getFormattedInitialDate()) . '</span>';
                $html .= '</div>';
            }
            
            if ($journal->getFormattedCreatedDate()) {
                $html .= '<div class="bvs-date">';
                $html .= '<span class="bvs-date-label">Criado:</span> ';
                $html .= '<span class="bvs-date-value">' . esc_html($journal->getFormattedCreatedDate()) . '</span>';
                $html .= '</div>';
            }
            
            if ($journal->getFormattedUpdatedDate()) {
                $html .= '<div class="bvs-date">';
                $html .= '<span class="bvs-date-label">Atualizado:</span> ';
                $html .= '<span class="bvs-date-value">' . esc_html($journal->getFormattedUpdatedDate()) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Tags: Thematic Area e Country
        if ($journal->getFormattedSubjectArea() || $journal->getFormattedCountry()) {
            $html .= '<div class="bvs-tags">';
            
            if ($journal->getFormattedSubjectArea()) {
                $html .= '<span class="bvs-tag bvs-tag-primary">' . esc_html($journal->getFormattedSubjectArea()) . '</span>';
            }
            
            if ($journal->getFormattedCountry()) {
                $html .= '<span class="bvs-tag bvs-tag-secondary">' . esc_html($journal->getFormattedCountry()) . '</span>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>'; // fecha bvs-item-info
        
        // Botão de acesso
        if ($journal->url) {
            $html .= '<div class="bvs-item-actions">';
            $html .= '<a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener" class="bvs-btn">';
            $html .= 'Acessar';
            $html .= '</a>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
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
    
    private function renderError(string $message): string {
        return '<div class="bvs-journals-error"><p><strong>Erro:</strong> ' . esc_html($message) . '</p></div>';
    }
    
    private function renderEmpty(): string {
        return '<div class="bvs-journals-empty"><p>' . esc_html__('Nenhum journal encontrado.', 'bvsalud-integrator') . '</p></div>';
    }
    
    public function enqueueAssets(): void {
        // Adicionar CSS específico para o shortcode
        wp_add_inline_style('bv-public', $this->getInlineCSS());
    }
    
    private function getInlineCSS(): string {
        return '
        .bvs-journals-container { margin: 20px 0; }
        .bvs-journals-header { margin-bottom: 15px; }
        .bvs-journals-count { font-size: 14px; color: #666; margin: 0; }
        .bvs-journals-list { display: flex; flex-direction: column; gap: 15px; }
        .bvs-journal-item { padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
        .journal-title { margin: 0 0 10px 0; font-size: 18px; }
        .journal-title a { color: #0073aa; text-decoration: none; }
        .journal-title a:hover { text-decoration: underline; }
        .journal-meta { display: flex; flex-wrap: wrap; gap: 10px; font-size: 14px; }
        .journal-meta span { display: inline-block; }
        .journal-compact { padding: 8px 0; border-bottom: 1px solid #eee; }
        .journal-compact:last-child { border-bottom: none; }
        .journal-detailed .journal-details p { margin: 5px 0; }
        .journal-link { display: inline-block; padding: 5px 10px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; font-size: 12px; }
        .journal-link:hover { background: #005a87; color: white; }
        
        /* Grid Template - Reusable for all resources */
        .bvs-grid { 
            display: grid; 
            gap: 24px 20px; 
            grid-template-columns: repeat(4, 1fr); 
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .bvs-grid[data-columns="3"] { grid-template-columns: repeat(3, 1fr); }
        .bvs-grid[data-columns="2"] { grid-template-columns: repeat(2, 1fr); }
        .bvs-grid[data-columns="5"] { grid-template-columns: repeat(5, 1fr); }
        .bvs-grid[data-columns="6"] { grid-template-columns: repeat(6, 1fr); }
        
        @media (max-width: 1200px) {
            .bvs-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .bvs-grid { grid-template-columns: repeat(2, 1fr); gap: 20px 15px; }
        }
        @media (max-width: 480px) {
            .bvs-grid { grid-template-columns: 1fr; gap: 15px; }
        }
        
        .bvs-grid .bvs-item {
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            box-sizing: border-box;
            min-height: 380px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .bvs-grid .bvs-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #0073aa;
        }
        
        .bvs-item-content { 
            display: flex; 
            flex-direction: column; 
            height: 100%;
            box-sizing: border-box;
        }
        
        .bvs-item-title { 
            margin: 0 0 16px 0; 
            font-size: 16px; 
            font-weight: 600; 
            line-height: 1.4;
            flex-shrink: 0;
            height: 44px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .bvs-item-title a { color: #2c3e50; text-decoration: none; }
        .bvs-item-title a:hover { color: #0073aa; }
        
        .bvs-item-info { 
            flex: 1 1 auto; 
            display: flex; 
            flex-direction: column; 
            gap: 10px;
            min-height: 200px;
        }
        
        .bvs-field { 
            margin: 0; 
            font-size: 13px; 
            line-height: 1.5;
            word-break: break-word;
            min-height: 20px;
        }
        
        .bvs-field-label { 
            font-weight: 600; 
            color: #495057;
            display: inline;
        }
        
        .bvs-field-value { 
            color: #6c757d;
            display: inline;
        }
        
        .bvs-icon { margin-right: 4px; }
        
        .bvs-dates { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 8px; 
            margin: 0; 
            font-size: 12px;
            line-height: 1.5;
            min-height: 24px;
        }
        
        .bvs-date { 
            display: inline;
            white-space: nowrap;
        }
        
        .bvs-date-label { 
            font-weight: 600; 
            color: #495057; 
        }
        
        .bvs-date-value { 
            color: #6c757d; 
        }
        
        .bvs-tags { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 6px; 
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e9ecef;
            min-height: 40px;
            align-items: flex-start;
        }
        
        .bvs-tag { 
            display: inline-block; 
            padding: 4px 10px; 
            border-radius: 12px; 
            font-size: 10px; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        .bvs-tag-primary { 
            background: #e3f2fd; 
            color: #1976d2; 
            border: 1px solid #bbdefb; 
        }
        
        .bvs-tag-secondary { 
            background: #f3e5f5; 
            color: #7b1fa2; 
            border: 1px solid #e1bee7; 
        }
        
        .bvs-item-actions { 
            margin-top: 16px;
            padding-top: 0;
            flex-shrink: 0;
        }
        
        .bvs-btn { 
            display: block; 
            width: 100%; 
            padding: 10px 16px; 
            background: #0073aa; 
            color: white !important; 
            text-align: center; 
            text-decoration: none; 
            border-radius: 4px; 
            font-size: 13px; 
            font-weight: 500; 
            transition: background-color 0.3s ease;
            box-sizing: border-box;
            border: none;
        }
        
        .bvs-btn:hover { 
            background: #005a87; 
            color: white !important; 
        }
        
        /* Pagination - Generic */
        .bvs-pagination { 
            margin-top: 20px; 
            text-align: center; 
        }
        .bvs-pagination .page-link { 
            display: inline-block; 
            padding: 8px 12px; 
            margin: 0 2px; 
            background: #f1f1f1; 
            color: #333; 
            text-decoration: none; 
            border-radius: 3px; 
            transition: background-color 0.3s ease;
        }
        .bvs-pagination .page-link:hover { 
            background: #ddd; 
        }
        .bvs-pagination .page-link.current { 
            background: #0073aa; 
            color: white; 
        }
        .bvs-pagination .page-dots {
            display: inline-block;
            padding: 8px 12px;
            color: #666;
        }
        
        /* Resource Container */
        .bvs-resources-container { 
            margin: 20px 0; 
        }
        .bvs-resources-header { 
            margin-bottom: 15px; 
        }
        .bvs-resources-count { 
            font-size: 14px; 
            color: #666; 
            margin: 0; 
        }
        
        /* Legacy support - backwards compatibility */
        .bvs-journals-grid { display: grid; gap: 24px 20px; grid-template-columns: repeat(4, 1fr); margin: 20px 0; }
        .bvs-journal-item { display: flex; flex-direction: column; height: 100%; box-sizing: border-box; min-height: 380px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
        .journal-grid-content { display: flex; flex-direction: column; height: 100%; box-sizing: border-box; }
        .journal-grid-title { margin: 0 0 16px 0; font-size: 16px; font-weight: 600; line-height: 1.4; height: 44px; overflow: hidden; }
        .journal-grid-info { flex: 1 1 auto; display: flex; flex-direction: column; gap: 10px; min-height: 200px; }
        .journal-grid-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e9ecef; min-height: 40px; }
        .journal-tag { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .journal-tag-theme { background: #e3f2fd; color: #1976d2; border: 1px solid #bbdefb; }
        .journal-tag-country { background: #f3e5f5; color: #7b1fa2; border: 1px solid #e1bee7; }
        .journal-grid-actions { margin-top: 16px; flex-shrink: 0; }
        .journal-access-btn { display: block; width: 100%; padding: 10px 16px; background: #0073aa; color: white !important; text-align: center; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .bvs-journals-pagination { margin-top: 20px; text-align: center; }
        .bvs-journals-pagination .page-link { display: inline-block; padding: 8px 12px; margin: 0 2px; background: #f1f1f1; color: #333; text-decoration: none; border-radius: 3px; }
        .bvs-journals-pagination .page-link:hover { background: #ddd; }
        .bvs-journals-pagination .page-link.current { background: #0073aa; color: white; }
        .bvs-journals-error { padding: 15px; background: #ffebee; border: 1px solid #f44336; border-radius: 5px; color: #c62828; }
        .bvs-journals-empty { padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 5px; text-align: center; color: #666; }
        ';
    }
}
