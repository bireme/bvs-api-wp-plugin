<?php
/**
 * Componente de Filtro com Checkboxes
 * 
 * Renderiza um grupo de checkboxes para filtros genéricos
 * 
 * @var array $filter Objeto de filtro contendo:
 *   - name: string Nome do filtro
 *   - facetKey: string Chave do campo (ex: 'bvsCountries[]')
 *   - filterOptions: array Array de opções {key, label, count}
 * @var array $selectedValues Array de valores atualmente selecionados
 */

if (!defined('ABSPATH'))
    exit;
?>

<div class="bvs-filter-group">
    <label class="bvs-filter-label"><?php echo esc_html($filter['name']); ?>:</label>

    <div class="bvs-checkbox-container">
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
                        <small class="bvs-count">(<?php echo $optionCount; ?>)</small>
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
</div>