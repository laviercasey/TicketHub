/**
 * Inventory Module JavaScript
 */
$(function() {

    // === Helper: get CSRF token from the page form ===
    function getCsrfToken() {
        return $('input[name="csrf_token"]').first().val() || '';
    }

    // === Custom modal dialog using project's modal styles ===
    function showInputModal(title, placeholder, callback) {
        // Remove existing modal if any
        $('#inv-input-modal').remove();

        var html = '<div id="inv-input-modal" class="modal-overlay">'
            + '<div class="modal-content" style="max-width:28rem">'
            + '  <div class="modal-header">'
            + '    <h3 class="text-lg font-semibold text-gray-900">' + title + '</h3>'
            + '    <button type="button" id="inv-modal-close" class="text-gray-400 hover:text-gray-600 transition-colors">'
            + '      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
            + '    </button>'
            + '  </div>'
            + '  <div class="modal-body">'
            + '    <label class="label mb-1">Название</label>'
            + '    <input type="text" id="inv-modal-input" class="input w-full" placeholder="' + placeholder + '" autocomplete="off">'
            + '    <p id="inv-modal-error" class="text-red-500 text-sm mt-1.5 hidden"></p>'
            + '  </div>'
            + '  <div class="modal-footer">'
            + '    <button type="button" id="inv-modal-cancel" class="btn-secondary">Отмена</button>'
            + '    <button type="button" id="inv-modal-ok" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Добавить</button>'
            + '  </div>'
            + '</div>'
            + '</div>';

        $('body').append(html);

        // Reinit lucide icons for new elements
        if (typeof lucide !== 'undefined') lucide.createIcons();

        var $modal = $('#inv-input-modal');
        var $input = $('#inv-modal-input');
        var $error = $('#inv-modal-error');
        var $okBtn = $('#inv-modal-ok');

        // Focus input
        setTimeout(function() { $input.focus(); }, 100);

        function closeModal() {
            $modal.remove();
        }

        function showError(msg) {
            $error.text(msg).removeClass('hidden');
            $input.addClass('input-error').focus();
        }

        function clearError() {
            $error.addClass('hidden');
            $input.removeClass('input-error');
        }

        function setLoading(loading) {
            if (loading) {
                $okBtn.prop('disabled', true).html('<svg class="animate-spin w-4 h-4 mr-1 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Сохранение...');
            } else {
                $okBtn.prop('disabled', false).html('<i data-lucide="plus" class="w-4 h-4"></i> Добавить');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        function submit() {
            var val = $.trim($input.val());
            if (!val) {
                showError('Введите название');
                return;
            }
            clearError();
            setLoading(true);
            callback(val, closeModal, function(errMsg) {
                setLoading(false);
                showError(errMsg || 'Ошибка сохранения');
            });
        }

        $okBtn.on('click', submit);
        $('#inv-modal-cancel, #inv-modal-close').on('click', closeModal);
        // Close on overlay click (but not on modal content click)
        $modal.on('click', function(e) {
            if ($(e.target).hasClass('modal-overlay')) closeModal();
        });
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submit();
            }
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        $input.on('input', function() {
            clearError();
        });
    }

    // === Show toast notification ===
    function showToast(message, type) {
        var colorClass = type === 'error' ? 'alert-danger' : 'alert-success';
        var iconName = type === 'error' ? 'alert-triangle' : 'check-circle';
        var $toast = $('<div class="' + colorClass + ' fixed top-4 right-4 z-[60] shadow-lg max-w-sm" style="display:none">'
            + '<i data-lucide="' + iconName + '" class="w-4 h-4 flex-shrink-0"></i>'
            + '<span>' + $('<span>').text(message).html() + '</span>'
            + '</div>');
        $('body').append($toast);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        $toast.fadeIn(200);
        setTimeout(function() { $toast.fadeOut(300, function() { $toast.remove(); }); }, 3000);
    }

    // === Cascading dropdown: Brand -> Model ===
    $('#inv-brand-select').on('change', function() {
        var brandId = $(this).val();
        var $modelSelect = $('#inv-model-select');
        var $customWrap = $('.custom-model-field');

        if (!brandId || brandId === 'other') {
            $modelSelect.html('<option value="">-- Выберите модель --</option>').prop('disabled', true);
            if (brandId === 'other') {
                $customWrap.show();
            }
            return;
        }

        $customWrap.hide().find('input').val('');
        $modelSelect.prop('disabled', true).html('<option value="">Загрузка...</option>');

        $.ajax({
            url: 'dispatch.php',
            data: { api: 'inventory', f: 'getModels', brand_id: brandId },
            dataType: 'json',
            timeout: 10000,
            success: function(data) {
                var html = '<option value="">-- Выберите модель --</option>';
                if (data && data.length) {
                    for (var i = 0; i < data.length; i++) {
                        html += '<option value="' + data[i].model_id + '">' + $('<span>').text(data[i].model_name).html() + '</option>';
                    }
                }
                html += '<option value="other">-- Другое (ввести вручную) --</option>';
                $modelSelect.html(html).prop('disabled', false);
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    showToast('Сессия истекла. Обновите страницу для повторной авторизации.', 'error');
                } else {
                    showToast('Не удалось загрузить модели. Вы можете ввести модель вручную.', 'error');
                }
                $modelSelect.html('<option value="">-- Выберите модель --</option><option value="other">-- Другое (ввести вручную) --</option>').prop('disabled', false);
            }
        });
    });

    // Model select - show/hide custom model field
    $('#inv-model-select').on('change', function() {
        var $customWrap = $('.custom-model-field');
        if ($(this).val() === 'other') {
            $customWrap.show();
            $(this).val('');
        } else {
            $customWrap.hide().find('input').val('');
        }
    });

    // === New brand quick add ===
    $('#inv-add-brand-btn').on('click', function(e) {
        e.preventDefault();

        showInputModal('Добавить бренд', 'Название бренда', function(name, closeModal, showError) {
            $.ajax({
                url: 'dispatch.php',
                method: 'POST',
                data: { api: 'inventory', f: 'addBrand', brand_name: name, csrf_token: getCsrfToken() },
                dataType: 'json',
                timeout: 10000,
                success: function(data) {
                    if (data && data.brand_id) {
                        var $sel = $('#inv-brand-select');
                        $sel.find('option[value="other"]').before(
                            '<option value="' + data.brand_id + '">' + $('<span>').text(data.brand_name).html() + '</option>'
                        );
                        $sel.val(data.brand_id).trigger('change');
                        closeModal();
                        showToast('Бренд "' + data.brand_name + '" добавлен', 'success');
                    } else {
                        showError(data.error || 'Не удалось добавить бренд');
                    }
                },
                error: function() {
                    showError('Ошибка сервера. Попробуйте снова.');
                }
            });
        });
    });

    // === New model quick add ===
    $('#inv-add-model-btn').on('click', function(e) {
        e.preventDefault();
        var brandId = $('#inv-brand-select').val();
        if (!brandId || brandId === 'other') {
            showToast('Сначала выберите бренд', 'error');
            return;
        }

        showInputModal('Добавить модель', 'Название модели', function(name, closeModal, showError) {
            $.ajax({
                url: 'dispatch.php',
                method: 'POST',
                data: { api: 'inventory', f: 'addModel', brand_id: brandId, model_name: name, csrf_token: getCsrfToken() },
                dataType: 'json',
                timeout: 10000,
                success: function(data) {
                    if (data && data.model_id) {
                        var $sel = $('#inv-model-select');
                        $sel.find('option[value="other"]').before(
                            '<option value="' + data.model_id + '">' + $('<span>').text(data.model_name).html() + '</option>'
                        );
                        $sel.val(data.model_id);
                        // Hide custom model field since we selected a proper model
                        $('.custom-model-field').hide().find('input').val('');
                        closeModal();
                        showToast('Модель "' + data.model_name + '" добавлена', 'success');
                    } else {
                        showError(data.error || 'Не удалось добавить модель');
                    }
                },
                error: function() {
                    showError('Ошибка сервера. Попробуйте снова.');
                }
            });
        });
    });

    // === Location tree toggle ===
    $(document).on('click', '.loc-toggle', function(e) {
        e.stopPropagation();
        var $li = $(this).closest('li');
        $li.children('ul').slideToggle(200);
        var $icon = $(this).find('i');
        if ($icon.hasClass('fa-caret-down')) {
            $icon.removeClass('fa-caret-down').addClass('fa-caret-right');
        } else {
            $icon.removeClass('fa-caret-right').addClass('fa-caret-down');
        }
    });

    // === Confirm delete ===
    $(document).on('click', '.inv-delete-btn', function(e) {
        if (!confirm('Вы уверены, что хотите удалить?')) {
            e.preventDefault();
            return false;
        }
    });

    // === Select all / deselect all ===
    $('#inv-select-all').on('change', function() {
        var checked = $(this).is(':checked');
        $('input.inv-checkbox').prop('checked', checked);
    });

    // === Filter auto-submit on select change ===
    $('.inv-filter-auto').on('change', function() {
        $(this).closest('form').submit();
    });

    // === Bulk actions ===
    $('#inv-bulk-action').on('click', function(e) {
        var checked = $('input.inv-checkbox:checked');
        if (checked.length === 0) {
            e.preventDefault();
            showToast('Выберите хотя бы одну запись', 'error');
            return false;
        }
    });

});
