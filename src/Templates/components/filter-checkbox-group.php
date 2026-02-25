<?php
/**
 * Componente de Filtro com Checkboxes
 *
 * Renderiza um grupo de checkboxes para filtros genéricos, com botão "Ver mais" quando há mais opções na API (fb).
 *
 * @var array $filter Objeto de filtro contendo:
 *   - name: string Nome do filtro
 *   - facetKey: string Chave do campo (ex: 'bvsCountries[]')
 *   - filterOptions: array Array de opções {key, label, count}
 *   - showMore: bool Se deve exibir botão "Ver mais"
 *   - nextFbCount: int Próxima quantidade a solicitar (ex: 40)
 * @var array $selectedValues Array de valores atualmente selecionados
 * @var string $resourceType Tipo do recurso (para o endpoint REST)
 * @var bool $showMore Se exibir botão "Ver mais"
 * @var int $nextFbCount Próximo fb_count para a API
 */

if (!defined('ABSPATH'))
    exit;

$showMore = isset($showMore) ? (bool) $showMore : false;
$nextFbCount = isset($nextFbCount) ? max(40, (int) $nextFbCount) : 40;
?>

<div class="bvs-filter-group" data-bv-facet-key="<?php echo esc_attr($filter['facetKey']); ?>">
    <label class="bvs-filter-label"><?php echo esc_html($filter['name']); ?>:</label>

    <div class="bvs-checkbox-container" data-bv-facet-key="<?php echo esc_attr($filter['facetKey']); ?>">
        <?php
        if (!empty($filter['filterOptions'])) {
            foreach ($filter['filterOptions'] as $option) {
                $optionKey = $option['key'];
                $optionLabel = $option['label'];
                $optionCount = $option['count'];
                $isChecked = in_array($optionKey, $selectedValues);
                ?>
                <label class="bvs-checkbox-item">
                    <input type="checkbox" name="<?php echo esc_attr($filter['facetKey']); ?>"
                        value="<?php echo esc_attr($optionKey); ?>" <?php echo $isChecked ? 'checked' : ''; ?>
                        class="bvs-checkbox">
                    <span class="bvs-checkbox-label">
                        <?php echo esc_html($optionLabel); ?>
                        <small class="bvs-count">(<?php echo (int) $optionCount; ?>)</small>
                    </span>
                </label>
                <?php
            }
        } else {
            ?>
            <p class="bvs-no-options">Nenhuma opção disponível</p>
            <?php
        }
        ?>
    </div>
    <?php if ($showMore): ?>
    <div class="bvs-filter-show-more-wrap">
        <button type="button" class="bvs-btn-show-more-facet" data-facet-key="<?php echo esc_attr($filter['facetKey']); ?>" data-next-fb-count="<?php echo (int) $nextFbCount; ?>">
            Ver mais
        </button>
    </div>
    <?php endif; ?>
</div>