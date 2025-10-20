// Admin scripts for BVSalud Integrator
(function(){
  'use strict';

  // Gerenciamento de recursos da API
  function initApiResources() {
    const container = document.getElementById('api-resources-container');
    if (!container) return;

    const addButton = document.getElementById('add-resource');
    const template = document.getElementById('resource-template');
    
    if (!addButton || !template) return;

    // Adicionar novo recurso
    addButton.addEventListener('click', function() {
      const resourceRows = container.querySelectorAll('.resource-row');
      const newIndex = resourceRows.length;
      
      const newRow = template.cloneNode(true);
      newRow.style.display = 'block';
      newRow.removeAttribute('id');
      
      // Substituir INDEX pelo índice real
      const inputs = newRow.querySelectorAll('input');
      inputs.forEach(input => {
        input.name = input.name.replace('INDEX', newIndex);
      });
      
      container.insertBefore(newRow, addButton);
    });

    // Remover recurso
    container.addEventListener('click', function(e) {
      if (e.target.classList.contains('remove-resource')) {
        e.target.closest('.resource-row').remove();
        reindexResources();
      }
    });

    // Reindexar recursos após remoção
    function reindexResources() {
      const resourceRows = container.querySelectorAll('.resource-row');
      resourceRows.forEach((row, index) => {
        const inputs = row.querySelectorAll('input');
        inputs.forEach(input => {
          input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
        });
      });
    }
  }

  // Inicializar quando o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApiResources);
  } else {
    initApiResources();
  }
})(); 


