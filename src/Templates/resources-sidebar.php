<?php
/**
 * Template da Sidebar de Filtros
 * 
 * Este template renderiza a sidebar de filtros para os recursos BVS
 * 
 * @var array $atts Atributos do shortcode
 * @var string $type Tipo de recurso
 * @var string $currentTitle Título atual
 * @var array $filters Array de filtros no formato Filter
 */

if (!defined('ABSPATH'))
    exit;
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

        <!-- Filtros Genéricos -->
        <?php
        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {
                // Determinar os valores selecionados baseado no filtro
                $selectedValues = [];
                $facetKey = $filter['facetKey'];

                // Buscar valores selecionados na URL
                if (isset($_GET[$facetKey]) && is_array($_GET[$facetKey])) {
                    $selectedValues = array_map('sanitize_text_field', $_GET[$facetKey]);
                } elseif (isset($_GET[$facetKey])) {
                    $selectedValues = explode(',', sanitize_text_field($_GET[$facetKey]));
                }

                // Incluir o componente genérico de filtro
                include __DIR__ . '/components/filter-checkbox-group.php';
            }
        }
        ?>

        <!-- Botões -->
        <div class="bvs-filter-actions">
            <button type="submit" class="bvs-btn-filter bvs-btn-primary">
                🔍 Buscar
            </button>
            <?php
            // Construir URL para limpar filtros
            // Obter todos os query args
            $urlParts = parse_url($_SERVER['REQUEST_URI']);
            $queryArgs = [];

            if (!empty($urlParts['query'])) {
                parse_str($urlParts['query'], $queryArgs);
            }

            // Lista de parâmetros para remover
            $paramsToRemove = [
                'bvsTitle',
                'bvsSearchTitle',
                'bvsPage',
                'bvsLimit',
                'bvsMax'
            ];

            // Adicionar facetKeys à lista de parâmetros para remover
            if (!empty($filters)) {
                foreach ($filters as $filter) {
                    $facetKey = $filter['facetKey'];
                    // Remover colchetes [] se existirem
                    $baseKey = rtrim($facetKey, '[]');
                    $paramsToRemove[] = $baseKey;
                    // Também adicionar versão sem colchetes no caso de estar na configuração
                    if ($baseKey !== $facetKey) {
                        $paramsToRemove[] = $facetKey;
                    }
                }
            }

            // Remover os parâmetros da lista (com e sem colchetes)
            foreach ($paramsToRemove as $param) {
                unset($queryArgs[$param]);
                unset($queryArgs[$param . '[]']);
            }

            // Construir nova URL
            $newQuery = !empty($queryArgs) ? '?' . http_build_query($queryArgs) : '';
            $path = isset($urlParts['path']) ? $urlParts['path'] : '';
            $clearUrl = $path . $newQuery;

            // Se não houver path, usar REQUEST_URI sem query
            if (empty($clearUrl)) {
                $clearUrl = strtok($_SERVER['REQUEST_URI'], '?');
            }
            ?>
            <a href="<?php echo esc_url($clearUrl); ?>" class="bvs-btn-filter bvs-btn-secondary">
                ✕ Limpar
            </a>
        </div>

        <!-- Filtros ativos -->
        <?php
        $hasActiveFilters = !empty($currentTitle);

        // Verificar se há filtros ativos nos filtros configurados
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $facetKey = $filter['facetKey'];
                if (isset($_GET[$facetKey]) && !empty($_GET[$facetKey])) {
                    $hasActiveFilters = true;
                    break;
                }
            }
        }
        ?>

        <?php if ($hasActiveFilters): ?>
            <div class="bvs-active-filters">
                <strong>Filtros ativos:</strong>

                <?php if (!empty($currentTitle)): ?>
                    <span class="bvs-filter-tag">
                        Título: <?php echo esc_html($currentTitle); ?>
                        <a href="<?php echo esc_url(remove_query_arg('bvsTitle')); ?>" class="bvs-remove-filter">×</a>
                    </span>
                <?php endif; ?>

                <?php
                // Mostrar filtros ativos dinamicamente
                if (!empty($filters)) {
                    foreach ($filters as $filter) {
                        $facetKey = $filter['facetKey'];
                        $filterName = $filter['name'];

                        if (isset($_GET[$facetKey]) && !empty($_GET[$facetKey])) {
                            $selectedValues = is_array($_GET[$facetKey])
                                ? $_GET[$facetKey]
                                : explode(',', $_GET[$facetKey]);

                            foreach ($selectedValues as $value) {
                                ?>
                                <span class="bvs-filter-tag">
                                    <?php echo esc_html($filterName); ?>: <?php echo esc_html($value); ?>
                                    <a href="<?php echo esc_url(remove_query_arg($facetKey)); ?>" class="bvs-remove-filter">×</a>
                                </span>
                                <?php
                            }
                        }
                    }
                }
                ?>
            </div>
        <?php endif; ?>
    </form>
</div>