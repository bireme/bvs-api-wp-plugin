<?php
namespace BV\Shortcodes;

use BV\API\BvsaludClient;
use BV\API\LegislationDto;
use BV\Support\ResourceCardDto;

if (!defined('ABSPATH'))
    exit;

/**
 * Shortcode [bvs_legislations] para exibir legislações da BVS
 * Funciona exatamente como BvsEventsShortcode, BvsWebResourcesShortcode e BvsJournalsShortcode
 */
final class BvsLegislationsShortcode
{

    public function register(): void
    {
        add_shortcode('bvs_legislations', [$this, 'render']);
    }

    public function render($atts, $content = ''): string
    {
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
            'show_fields' => 'title,type,country,scope',
            'showFilters' => 'false',
            'showfilters' => 'false',
        ], $atts, 'bvs_legislations');

        // Parâmetros da URL sobrescrevem os do shortcode
        $urlParams = [
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


        // Processar checkboxes de tipos de ato
        if (isset($_GET['bvsTypes']) && is_array($_GET['bvsTypes'])) {
            $selectedTypes = array_map('sanitize_text_field', $_GET['bvsTypes']);
            $atts['type'] = implode(',', $selectedTypes);
        }

        $atts['limit'] = max(1, min(100, (int) $atts['limit']));
        $atts['max'] = max(1, min(500, (int) $atts['max']));
        $atts['page'] = max(1, (int) $atts['page']);
        $atts['show_pagination'] = $atts['show_pagination'] === 'true';
        $atts['showFilters'] = $atts['showFilters'] === 'true';

        $client = BvsaludClient::forLegislations();
        $legislations = [];
        $totalLegislations = 0;
        $error = null;
        $results = [];

        try {
            $connectionTest = $client->testLegislationsConnection();
            if (!$connectionTest['success']) {
                return $this->renderError('Erro de conexão com a API BVS: ' . $connectionTest['message']);
            }

            $searchTitle = !empty($atts['searchTitle']) ? trim($atts['searchTitle']) : '';
            $search = !empty($atts['search']) ? trim($atts['search']) : '';
            $subject = !empty($atts['subject']) ? trim($atts['subject']) : '';
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
                        $typeFilter = $this->buildActTypeFilter($singleType);
                        $typeQueries[] = 'act_type:' . $typeFilter;
                    }
                    $queryParts[] = '(' . implode(' OR ', $typeQueries) . ')';
                } else {
                    $typeFilter = $this->buildActTypeFilter($type);
                    $queryParts[] = 'act_type:' . $typeFilter;
                }
            }

            if (!empty($subject)) {
                $queryParts[] = 'descriptor:"' . $subject . '"';
            }

            $hasType = !empty($type);

            $finalQuery = !empty($queryParts) ? implode(' AND ', $queryParts) : '*:*';

            $searchParams = [
                'q' => $finalQuery,
                'count' => $atts['limit'],
                'start' => ($atts['page'] - 1) * $atts['limit']
            ];

            if (!$atts['show_pagination']) {
                $firstCall = $client->searchLegislations(array_merge($searchParams, ['count' => 1, 'start' => 0]));
                $totalLegislations = $firstCall['total'] ?? 0;

                $searchParams['count'] = min($totalLegislations, $atts['max']);
                $searchParams['start'] = 0;
                $results = $client->searchLegislations($searchParams);
            } else {
                $results = $client->searchLegislations($searchParams);
                $totalLegislations = $results['total'] ?? 0;
            }

            // Converter arrays para DTOs
            $rawLegislations = $results['legislations'] ?? [];
            $legislations = array_filter(
                array_map(function ($legislation) {
                    if ($legislation instanceof LegislationDto) {
                        return $legislation;
                    }
                    $dto = new LegislationDto($legislation);
                    return $dto->isValid() ? $dto : null;
                }, $rawLegislations)
            );

        } catch (\Exception $e) {
            return $this->renderError('Erro ao buscar legislações: ' . $e->getMessage());
        }

        if (empty($legislations)) {
            $content = $this->renderEmpty();
        } else {
            $content = $this->renderLegislations($legislations, $atts, $totalLegislations);
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
    private function renderWithFilters(string $content, array $atts): string
    {
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
    private function renderFiltersSidebar(array $atts): string
    {
        // Pegar valores atuais dos filtros (da URL ou do shortcode)
        $currentTitle = $_GET['bvsTitle'] ?? $_GET['bvsSearchTitle'] ?? $atts['searchTitle'] ?? '';
        $currentSubject = $_GET['bvsSubject'] ?? $atts['subject'] ?? '';
        $currentType = $_GET['bvsType'] ?? $atts['type'] ?? '';

        // Criar cliente para buscar tipos de atos
        $client = BvsaludClient::forLegislations();

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
                    <input type="text" id="bvsTitle" name="bvsTitle" class="bvs-filter-input" placeholder="Digite o título..."
                        value="<?php echo esc_attr($currentTitle); ?>">
                </div>


                <!-- Filtros de Tipo de Ato -->
                <div class="bvs-filter-group">
                    <label class="bvs-filter-label">Tipo de Ato:</label>

                    <?php
                    // Obter tipos de atos disponíveis da API
                    $availableActTypes = $client->getAvailableActTypes();
                    ?>

                    <div class="bvs-checkbox-container">
                        <?php
                        if (!empty($availableActTypes)) {
                            foreach ($availableActTypes as $actType) {
                                $typeName = $actType['name'];
                                $typeCount = $actType['count'];
                                $isChecked = in_array($typeName, $selectedTypes);
                                ?>
                                <label class="bvs-checkbox-item">
                                    <input type="checkbox" name="bvsTypes[]" value="<?php echo esc_attr($typeName); ?>" <?php echo $isChecked ? 'checked' : ''; ?> class="bvs-checkbox">
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
                                <p>⚠️ Tipos de atos não disponíveis</p>
                                <small>Verifique se a URL da API de legislações está configurada corretamente nas configurações do
                                    plugin.</small>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <!-- Botões -->
                <div class="bvs-filter-actions">
                    <button type="submit" class="bvs-btn-filter bvs-btn-primary">Buscar</button>
                    <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>"
                        class="bvs-btn-filter bvs-btn-secondary">
                        Limpar
                    </a>
                </div>

                <!-- Filtros ativos -->
                <?php if (!empty($currentTitle) || !empty($currentSubject) || !empty($currentType)): ?>
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
                        <?php if (!empty($currentType)): ?>
                            <span class="bvs-filter-tag">
                                Tipo: <?php echo esc_html($currentType); ?>
                                <a href="<?php echo esc_url(remove_query_arg(['bvsType', 'bvsTypes'])); ?>"
                                    class="bvs-remove-filter">×</a>
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
     * Constrói filtro de tipo de ato usando o formato exato dos facets da API
     */
    private function buildActTypeFilter(string $actType): string
    {
        // Buscar o formato correto nos facets da API
        $client = BvsaludClient::forLegislations();
        $facets = $client->getFacetFields(['act_type']);

        if (!isset($facets['error'])) {
            // Tentar encontrar o tipo nos facets
            $typeFacets = null;

            if (isset($facets['diaServerResponse'][0]['facet_counts']['facet_fields']['act_type'])) {
                $typeFacets = $facets['diaServerResponse'][0]['facet_counts']['facet_fields']['act_type'];
            } elseif (isset($facets['facet_counts']['facet_fields']['act_type'])) {
                $typeFacets = $facets['facet_counts']['facet_fields']['act_type'];
            } elseif (isset($facets['response']['facet_counts']['facet_fields']['act_type'])) {
                $typeFacets = $facets['response']['facet_counts']['facet_fields']['act_type'];
            }

            if ($typeFacets && is_array($typeFacets)) {
                $typeName = $this->extractPortugueseText($actType);

                // Procurar o tipo nos facets
                foreach ($typeFacets as $facet) {
                    if (is_array($facet) && isset($facet[0]) && isset($facet[1])) {
                        $typeRaw = $facet[0];
                        $facetTypeName = $this->extractPortugueseText($typeRaw);

                        // Se encontrou o tipo, usar o formato exato do facet
                        if (strtolower($facetTypeName) === strtolower($typeName)) {
                            return '"' . $typeRaw . '"';
                        }
                    }
                }
            }
        }

        // Se não encontrou nos facets, usar o nome como está
        return '"' . $actType . '"';
    }

    /**
     * Extrai texto em português de campos multilíngues
     */
    private function extractPortugueseText(string $text): string
    {
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

    private function renderLegislations(array $legislations, array $atts, int $total): string
    {
        $showFields = array_map('trim', explode(',', $atts['show_fields']));

        // Sempre usa o sistema genérico de grid
        return $this->renderGenericGrid($legislations, $atts, $total, $showFields);
    }

    /**
     * Renderiza usando o sistema genérico de grid
     */
    private function renderGenericGrid(array $legislations, array $atts, int $total, array $showFields): string
    {
        // Converte LegislationDto[] para ResourceCardDto[]
        $cards = array_map(function ($legislation) use ($showFields) {
            return $this->convertLegislationToCard($legislation, $showFields);
        }, $legislations);

        // Remove legislações inválidas
        $cards = array_filter($cards, function ($card) {
            return $card->isValid();
        });

        // Carrega o template genérico
        $templatePath = trailingslashit(__DIR__) . '../Templates/bvs-grid.php';

        if (file_exists($templatePath)) {
            ob_start();
            // Passa os cards convertidos para o template
            $resources = $cards;
            include $templatePath;
            return ob_get_clean();
        }

        return $this->renderFallback($legislations, $atts, $total);
    }

    /**
     * Converte LegislationDto para ResourceCardDto
     */
    private function convertLegislationToCard(LegislationDto $legislation, array $showFields): ResourceCardDto
    {
        // 1. TÍTULO
        $title = '';
        if (in_array('title', $showFields) && $legislation->title) {
            $titleText = strlen($legislation->title) > 60 ? substr($legislation->title, 0, 57) . '...' : $legislation->title;
            if ($legislation->url) {
                $title = '<a href="' . esc_url($legislation->url) . '" target="_blank" rel="noopener">' . esc_html($titleText) . '</a>';
            } else {
                $title = esc_html($titleText);
            }
        }

        // 2. CONTEÚDO (HTML formatado)
        ob_start();
        ?>

        <?php if (!empty($legislation->description)): ?>
            <div class="bvs-field">
                <p class="bvs-abstract"><?= esc_html($this->truncateText($legislation->description, 120)) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($legislation->getFormattedActNumber()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Número:</span>
                <span class="bvs-field-value"><?= esc_html($legislation->getFormattedActNumber()) ?></span>
            </div>
        <?php endif; ?>

        <?php if (in_array('type', $showFields) && $legislation->getFormattedActType()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Tipo:</span>
                <span class="bvs-field-value"><?= esc_html($legislation->getFormattedActType()) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($legislation->getFormattedIssuerOrgan()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Órgão:</span>
                <span
                    class="bvs-field-value"><?= esc_html($this->truncateText($this->extractMultilangText($legislation->issuer_organ), 50)) ?></span>
            </div>
        <?php endif; ?>



        <?php if (in_array('keywords', $showFields) && $legislation->getDescriptorsString()): ?>
            <div class="bvs-field">
                <span class="bvs-field-label">Descritores:</span>
                <span class="bvs-field-value"><?= esc_html($this->truncateText($legislation->getDescriptorsString(), 50)) ?></span>
            </div>
        <?php endif; ?>



        <?php if ($legislation->getFormattedIssueDate() || $legislation->getFormattedPublicationDate()): ?>
            <div class="bvs-dates">
                <?php if ($legislation->getFormattedIssueDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Emissão:</span>
                        <span class="bvs-date-value"><?= esc_html($legislation->getFormattedIssueDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($legislation->getFormattedPublicationDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Publicação:</span>
                        <span class="bvs-date-value"><?= esc_html($legislation->getFormattedPublicationDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($legislation->getFormattedCreatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Criado:</span>
                        <span class="bvs-date-value"><?= esc_html($legislation->getFormattedCreatedDate()) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($legislation->getFormattedUpdatedDate()): ?>
                    <div class="bvs-date">
                        <span class="bvs-date-label">Atualizado:</span>
                        <span class="bvs-date-value"><?= esc_html($legislation->getFormattedUpdatedDate()) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
        $content = ob_get_clean();

        // 3. TAGS
        $tags = [];
        if ($legislation->getFormattedSubjectArea()) {
            $tags[] = $legislation->getFormattedSubjectArea();
        }
        if ($legislation->getFormattedCountry()) {
            $tags[] = $legislation->getFormattedCountry();
        }
        if ($legislation->getFormattedActType()) {
            $tags[] = $legislation->getFormattedActType();
        }

        // 4. LINK - extrair do campo fulltext multilíngue
        $link = $this->extractUrlFromFulltext($legislation->fulltext ?? []);

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
    private function renderFallback(array $legislations, array $atts, int $total): string
    {
        if (empty($legislations)) {
            return '<div class="bvs-legislations-container"><p>Nenhuma legislação encontrada.</p></div>';
        }

        ob_start();
        ?>
        <div class="bvs-legislations-container">
            <div class="bvs-legislations-header">
                <p class="bvs-legislations-count"><?php echo $total; ?> legislações encontradas</p>
            </div>
            <div class="bvs-legislations-list">
                <?php foreach ($legislations as $legislation): ?>
                    <div class="bvs-legislation-item">
                        <h3 class="legislation-title">
                            <a href="<?php echo esc_url($legislation->url ?? '#'); ?>" target="_blank">
                                <?php echo esc_html($legislation->title ?? 'Sem título'); ?>
                            </a>
                        </h3>
                        <?php if ($legislation->description): ?>
                            <p><?php echo esc_html($legislation->description); ?></p>
                        <?php endif; ?>
                        <div class="legislation-meta">
                            <?php if ($legislation->act_type): ?>
                                <span><strong>Tipo:</strong> <?php echo esc_html($legislation->getFormattedActType()); ?></span>
                            <?php endif; ?>
                            <?php if ($legislation->act_number): ?>
                                <span><strong>Número:</strong> <?php echo esc_html($legislation->getFormattedActNumber()); ?></span>
                            <?php endif; ?>
                            <?php if ($legislation->country): ?>
                                <span><strong>País:</strong> <?php echo esc_html($legislation->getFormattedCountry()); ?></span>
                            <?php endif; ?>

                            <?php if ($legislation->scope): ?>
                                <span><strong>Escopo:</strong> <?php echo esc_html($legislation->scope); ?> (raw:
                                    <?php echo esc_html($legislation->scope); ?>)</span>
                            <?php endif; ?>
                            <?php if ($legislation->scope_state): ?>
                                <span><strong>Estado:</strong> <?php echo esc_html($legislation->scope_state); ?> (raw:
                                    <?php echo esc_html($legislation->scope_state); ?>)</span>
                            <?php endif; ?>
                            <?php if ($legislation->scope_city): ?>
                                <span><strong>Cidade:</strong> <?php echo esc_html($legislation->scope_city); ?> (raw:
                                    <?php echo esc_html($legislation->scope_city); ?>)</span>
                            <?php endif; ?>
                            <?php if ($legislation->issuer_organ): ?>
                                <span><strong>Órgão:</strong> <?php echo esc_html($legislation->issuer_organ); ?> (raw:
                                    <?php echo esc_html($legislation->issuer_organ); ?>)</span>
                            <?php endif; ?>
                            <?php if ($legislation->source_name): ?>
                                <span><strong>Fonte:</strong> <?php echo esc_html($legislation->source_name); ?> (raw:
                                    <?php echo esc_html($legislation->source_name); ?>)</span>
                            <?php endif; ?>

                            <?php if ($legislation->getFormattedIssueDate()): ?>
                                <span><strong>Emissão:</strong> <?php echo esc_html($legislation->getFormattedIssueDate()); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function renderError(string $message): string
    {
        return '<div class="bvs-error"><p><strong>Erro:</strong> ' . esc_html($message) . '</p></div>';
    }

    private function renderEmpty(): string
    {
        return '<div class="bvs-empty"><p>' . esc_html__('Nenhuma legislação encontrada.', 'bvsalud-integrator') . '</p></div>';
    }

    /**
     * Extrai texto multilíngue de forma segura
     * Formato esperado: "pt-br^Texto|en^Text"
     * 
     * @param string|null $text Texto multilíngue
     * @return string Texto extraído ou string vazia se não encontrado
     */
    private function extractMultilangText(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Primeiro explode por | para separar os idiomas
        $langParts = explode('|', $text);
        if (empty($langParts) || !isset($langParts[0])) {
            return $text; // Retorna o texto original se não tiver separadores
        }

        // Pega a primeira parte e explode por ^
        $firstPart = $langParts[0];
        $textParts = explode('^', $firstPart);

        // Se tem pelo menos 2 partes (idioma^texto), retorna o texto
        if (count($textParts) >= 2 && isset($textParts[1])) {
            return $textParts[1];
        }

        // Se não tem o formato esperado, retorna o texto original
        return $firstPart;
    }

    private function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Extrai URL do campo fulltext multilíngue
     * Formato esperado: ["es|https://example.com", "en|https://example.com"]
     * 
     * @param array $fulltext Array de strings no formato multilíngue
     * @return string URL extraída ou string vazia se não encontrada
     */
    private function extractUrlFromFulltext(array $fulltext): string
    {
        if (empty($fulltext)) {
            return '';
        }

        foreach ($fulltext as $item) {
            if (empty($item)) {
                continue;
            }

            // Se contém |, é formato multilíngue
            if (strpos($item, '|') !== false) {
                $parts = explode('|', $item, 2);
                if (count($parts) === 2) {
                    $url = trim($parts[1]);
                    // Verifica se é uma URL válida
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        return $url;
                    }
                }
            } else {
                // Se não tem |, pode ser uma URL direta
                if (filter_var($item, FILTER_VALIDATE_URL)) {
                    return $item;
                }
            }
        }

        return '';
    }
}

