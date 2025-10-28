// Admin scripts for BVSalud Integrator
(function(){
  'use strict';

  let currentFilters = [];
  let currentResourceIndex = null;

  // Gerenciamento de recursos da API
  function initApiResources() {
    const container = document.getElementById('api-resources-container');
    if (!container) return;

    const addButton = document.getElementById('add-resource');
    const template = document.getElementById('resource-template');
    
    if (!addButton || !template) return;

    // Adicionar novo recurso
    addButton.addEventListener('click', function() {
      const tbody = container.querySelector('tbody');
      if (!tbody) return;
      
      // Encontrar todas as linhas exceto o template
      const resourceRows = tbody.querySelectorAll('tr.resource-row:not(#resource-template)');
      const newIndex = resourceRows.length;
      
      const newRow = template.cloneNode(true);
      newRow.classList.add('resource-row');
      newRow.style.display = 'table-row';
      newRow.removeAttribute('id');
      
      // Substituir INDEX pelo índice real
      replaceIndexInRow(newRow, 'INDEX', newIndex, newIndex);
      
      tbody.appendChild(newRow);
    });

    // Remover recurso
    container.addEventListener('click', function(e) {
      if (e.target.classList.contains('remove-resource')) {
        const row = e.target.closest('tr.resource-row');
        if (row) {
          row.remove();
          reindexResources();
        }
      }
    });

    // Reindexar recursos após remoção
    function reindexResources() {
      const tbody = container.querySelector('tbody');
      if (!tbody) return;
      
      const resourceRows = tbody.querySelectorAll('tr.resource-row');
      resourceRows.forEach((row, index) => {
        replaceIndexInRow(row, /\[\d+\]/, '[' + index + ']', index);
      });
    }

    // Função auxiliar para substituir índices nos inputs
    function replaceIndexInRow(row, oldIndex, newIndex, actualIndex) {
      const inputs = row.querySelectorAll('input, button, script');
      inputs.forEach(input => {
        if (input.name) {
          input.name = input.name.replace(oldIndex, '[' + actualIndex + ']');
        }
        if (input.getAttribute('data-index') !== null) {
          input.setAttribute('data-index', actualIndex);
        }
        if (input.className && input.className.includes('filter-data-')) {
          input.className = 'filter-data-' + actualIndex;
        }
      });
    }
  }

  // Inicializar gerenciamento de filtros
  function initFiltersManagement() {
    const modal = document.getElementById('filters-modal');
    const modalClose = document.querySelectorAll('.filters-modal-close');
    const addFilterBtn = document.getElementById('add-filter-btn');
    const saveFiltersBtn = document.getElementById('save-filters-btn');
    const filtersContainer = document.getElementById('filters-container');

    if (!modal) return;

    // Usar delegação de eventos para botões de gerenciar filtros
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('manage-filters-btn')) {
        currentResourceIndex = e.target.getAttribute('data-index');
        openFiltersModal(currentResourceIndex);
      }
    });

    // Fechar modal
    modalClose.forEach(btn => {
      btn.addEventListener('click', closeFiltersModal);
    });

    modal.querySelector('.filters-modal-overlay').addEventListener('click', closeFiltersModal);

    // Adicionar novo filtro
    if (addFilterBtn) {
      addFilterBtn.addEventListener('click', function() {
        addFilterItem();
      });
    }

    // Salvar filtros
    if (saveFiltersBtn) {
      saveFiltersBtn.addEventListener('click', function() {
        saveFilters();
      });
    }

    // Drag and drop será inicializado dinamicamente quando o modal for aberto
  }

  // Abrir modal de filtros
  function openFiltersModal(resourceIndex) {
    const modal = document.getElementById('filters-modal');
    const resourceRow = document.querySelectorAll('.resource-row')[parseInt(resourceIndex)];
    
    if (!resourceRow || !modal) return;

    // Obter filtros existentes do campo JSON
    currentFilters = [];
    const filterDataScript = resourceRow.querySelector('script.filter-data-' + resourceIndex);
    
    if (filterDataScript) {
      try {
        currentFilters = JSON.parse(filterDataScript.textContent);
      } catch (e) {
        console.error('Erro ao parsear dados dos filtros:', e);
      }
    } else {
      // Fallback para o método antigo (de inputs hidden)
      const filtersDisplay = resourceRow.querySelector('.filters-display[data-index="' + resourceIndex + '"]');
      if (filtersDisplay) {
        const hiddenInputs = filtersDisplay.querySelectorAll('.filter-item');
        hiddenInputs.forEach(item => {
          const keyInput = item.querySelector('input[name*="[key]"]');
          const labelInput = item.querySelector('input[name*="[label]"]');
          if (keyInput && labelInput) {
            currentFilters.push({
              key: keyInput.value,
              label: labelInput.value
            });
          }
        });
      }
    }

    renderFilters();
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  // Fechar modal
  function closeFiltersModal() {
    const modal = document.getElementById('filters-modal');
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
  }

  // Renderizar filtros no modal
  function renderFilters() {
    const container = document.getElementById('filters-container');
    if (!container) return;

    container.innerHTML = '';

    if (currentFilters.length === 0) {
      container.innerHTML = '<div class="empty-filters">Nenhum filtro adicionado</div>';
      return;
    }

    currentFilters.forEach((filter, index) => {
      container.appendChild(createFilterItem(filter, index));
    });
  }

  // Criar item de filtro
  function createFilterItem(filter, index) {
    const item = document.createElement('div');
    item.className = 'filter-item-draggable';
    item.setAttribute('draggable', 'true');
    item.setAttribute('data-index', index);

    item.innerHTML = `
      <div style="display: flex; align-items: center; flex: 1;">
        <span class="filter-handle">⋮⋮</span>
        <div class="filter-inputs">
          <input type="text" class="filter-key" placeholder="Chave (key)" value="${filter.key || ''}" />
          <input type="text" class="filter-label" placeholder="Rótulo (label)" value="${filter.label || ''}" />
        </div>
      </div>
      <div class="filter-actions">
        <button type="button" class="filter-remove-btn remove-filter-item">×</button>
      </div>
    `;

    // Event listeners
    item.querySelector('.remove-filter-item').addEventListener('click', function() {
      currentFilters.splice(index, 1);
      renderFilters();
    });

    // Edição inline
    item.querySelector('.filter-key').addEventListener('input', function() {
      currentFilters[index].key = this.value;
    });

    item.querySelector('.filter-label').addEventListener('input', function() {
      currentFilters[index].label = this.value;
    });

    return item;
  }

  // Adicionar novo filtro
  function addFilterItem() {
    currentFilters.push({ key: '', label: '' });
    renderFilters();
  }

  // Salvar filtros
  function saveFilters() {
    if (!currentResourceIndex && currentResourceIndex !== 0) return;

    const resourceRow = document.querySelectorAll('.resource-row')[parseInt(currentResourceIndex)];
    if (!resourceRow) return;

    // Encontrar ou criar o container de display
    let filtersDisplay = resourceRow.querySelector('.filters-display[data-index="' + currentResourceIndex + '"]');
    
    if (!filtersDisplay) {
      filtersDisplay = document.createElement('div');
      filtersDisplay.className = 'filters-display';
      filtersDisplay.setAttribute('data-index', currentResourceIndex);
      filtersDisplay.style.display = 'none';
      
      const filtersCompact = resourceRow.querySelector('.filters-compact');
      if (filtersCompact) {
        filtersCompact.appendChild(filtersDisplay);
      }
    }

    filtersDisplay.innerHTML = '';

    // Criar inputs hidden
    currentFilters.forEach((filter, index) => {
      const item = document.createElement('div');
      item.className = 'filter-item';
      item.innerHTML = `
        <input type="hidden" name="bv_api_resources[${currentResourceIndex}][filter_types][${index}][key]" value="${filter.key}" />
        <input type="hidden" name="bv_api_resources[${currentResourceIndex}][filter_types][${index}][label]" value="${filter.label}" />
      `;
      filtersDisplay.appendChild(item);
    });

    // Atualizar o contador de filtros
    const filterCount = resourceRow.querySelector('.filter-count');
    if (filterCount) {
      filterCount.textContent = currentFilters.length;
    }

    // Atualizar o script JSON com os dados
    let filterDataScript = resourceRow.querySelector('script.filter-data-' + currentResourceIndex);
    if (!filterDataScript) {
      filterDataScript = document.createElement('script');
      filterDataScript.type = 'application/json';
      filterDataScript.className = 'filter-data-' + currentResourceIndex;
      const filtersCompact = resourceRow.querySelector('.filters-compact');
      if (filtersCompact) {
        filtersCompact.appendChild(filterDataScript);
      }
    }
    filterDataScript.textContent = JSON.stringify(currentFilters);

    closeFiltersModal();
  }

  // Variável global para o elemento arrastado
  let draggedElement = null;

  // Inicializar drag and drop (será chamado uma vez)
  function initDragAndDrop() {
    const container = document.getElementById('filters-container');
    if (!container) return;

    // Usar event delegation para todos os eventos de drag
    container.addEventListener('dragstart', function(e) {
      const item = e.target.closest('.filter-item-draggable');
      if (item) {
        draggedElement = item;
        item.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
      }
    });

    container.addEventListener('dragend', function(e) {
      if (draggedElement) {
        draggedElement.classList.remove('dragging');
      }
      // Limpar todas as classes drag-over
      container.querySelectorAll('.drag-over').forEach(el => {
        el.classList.remove('drag-over');
      });
    });

    container.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      
      const target = e.target.closest('.filter-item-draggable');
      if (target && target !== draggedElement) {
        // Remover drag-over de todos
        container.querySelectorAll('.drag-over').forEach(el => {
          el.classList.remove('drag-over');
        });
        target.classList.add('drag-over');
      }
    });

    container.addEventListener('dragleave', function(e) {
      const target = e.target.closest('.filter-item-draggable');
      if (target) {
        target.classList.remove('drag-over');
      }
    });

    container.addEventListener('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      // Remover drag-over de todos
      container.querySelectorAll('.drag-over').forEach(el => {
        el.classList.remove('drag-over');
      });

      const target = e.target.closest('.filter-item-draggable');
      
      if (!draggedElement || !target || draggedElement === target) {
        draggedElement = null;
        return;
      }

      // Obter índices
      const allItems = Array.from(container.querySelectorAll('.filter-item-draggable'));
      const draggedIndex = allItems.indexOf(draggedElement);
      const targetIndex = allItems.indexOf(target);

      if (draggedIndex !== -1 && targetIndex !== -1 && draggedIndex !== targetIndex) {
        // Reordenar array
        const temp = currentFilters[draggedIndex];
        currentFilters.splice(draggedIndex, 1);
        currentFilters.splice(targetIndex, 0, temp);

        // Re-renderizar
        renderFilters();
      }

      draggedElement = null;
    });
  }

  // Remover inputs vazios antes do submit
  function cleanupEmptyResources() {
    const form = document.querySelector('form[action*="options.php"]');
    if (form) {
      form.addEventListener('submit', function(e) {
        const tbody = document.querySelector('#api-resources-container tbody');
        if (!tbody) return;
        
        // Remover a linha template se existir
        const template = tbody.querySelector('#resource-template');
        if (template) {
          template.remove();
        }
        
        // Remover recursos com todos os campos vazios
        const resourceRows = tbody.querySelectorAll('tr.resource-row');
        resourceRows.forEach(row => {
          const resourceInput = row.querySelector('input[name*="[resource]"]');
          const urlInput = row.querySelector('input[name*="[base_url]"]');
          
          if (resourceInput && urlInput && 
              !resourceInput.value.trim() && !urlInput.value.trim()) {
            row.remove();
          }
        });
      });
    }
  }

  // Inicializar quando o DOM estiver pronto
  function init() {
    initApiResources();
    initFiltersManagement();
    initDragAndDrop();
    cleanupEmptyResources();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
