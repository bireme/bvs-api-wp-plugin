/**
 * Filtros BVS - "Ver mais" para carregar mais opções de facet via parâmetro fb da API
 */
(function () {
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function onShowMoreClick(ev) {
        var button = ev.currentTarget;
        if (button.disabled) return;
        var form = button.closest('form#bvsFiltersForm');
        if (!form) return;

        var restUrl = form.getAttribute('data-bv-rest-url');
        var resourceType = form.getAttribute('data-bv-resource-type');
        var currentQ = form.getAttribute('data-bv-current-q') || '*:*';
        var currentFq = form.getAttribute('data-bv-current-fq') || '';
        var facetKey = button.getAttribute('data-facet-key');
        var fbCount = parseInt(button.getAttribute('data-next-fb-count'), 10) || 40;

        if (!restUrl || !resourceType || !facetKey) return;

        var container = button.closest('.bvs-filter-group').querySelector('.bvs-checkbox-container[data-bv-facet-key="' + escapeHtml(facetKey) + '"]');
        if (!container) return;

        var existingCheckboxes = container.querySelectorAll('input.bvs-checkbox');
        var selectedValues = [];
        for (var i = 0; i < existingCheckboxes.length; i++) {
            if (existingCheckboxes[i].checked) {
                selectedValues.push(existingCheckboxes[i].value);
            }
        }

        button.disabled = true;
        button.textContent = 'Carregando…';

        var params = new URLSearchParams({
            resource_type: resourceType,
            facet_key: facetKey,
            fb_count: String(fbCount),
            q: currentQ,
            fq: currentFq
        });
        var url = restUrl + '?' + params.toString();

        fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    button.textContent = 'Ver mais';
                    button.disabled = false;
                    return;
                }
                var options = data.options || [];
                var facetKeyAttr = escapeHtml(facetKey);
                var html = '';
                for (var j = 0; j < options.length; j++) {
                    var opt = options[j];
                    var key = escapeHtml(opt.key);
                    var label = escapeHtml(opt.label);
                    var count = parseInt(opt.count, 10) || 0;
                    var checked = selectedValues.indexOf(opt.key) !== -1 ? ' checked' : '';
                    html += '<label class="bvs-checkbox-item">' +
                        '<input type="checkbox" name="' + facetKeyAttr + '" value="' + key + '" class="bvs-checkbox"' + checked + '>' +
                        '<span class="bvs-checkbox-label">' + label + ' <small class="bvs-count">(' + count + ')</small></span>' +
                        '</label>';
                }
                container.innerHTML = html;

                var nextCount = fbCount + 20;
                button.setAttribute('data-next-fb-count', String(nextCount));
                button.textContent = 'Ver mais';
                button.disabled = false;

                if (!data.hasMore) {
                    button.closest('.bvs-filter-show-more-wrap').style.display = 'none';
                }
            })
            .catch(function () {
                button.textContent = 'Ver mais';
                button.disabled = false;
            });
    }

    function init() {
        document.querySelectorAll('.bvs-btn-show-more-facet').forEach(function (btn) {
            if (btn.dataset.bvShowMoreBound) return;
            btn.dataset.bvShowMoreBound = '1';
            btn.addEventListener('click', onShowMoreClick);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
