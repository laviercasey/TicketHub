/**
 * Created by mansurov on 04.05.2016.
 */
function onSelectAssign(id,a){
    var ClickedElement = document.getElementById("morestaffs_id_" + id);
    var ClickedStaffId = ClickedElement.value;
    if (a) ClickedElement.checked = !ClickedElement.checked;
    var ClickedStaffNewCheckedValue = ClickedElement.checked;
    var ClickedElement_a = document.getElementById('morestaffs_a_id_' + ClickedStaffId);
    var staffId_element = document.getElementById('staffId');
    var staffId = staffId_element.value;

    // Get clean staff name (remove any existing badges)
    var staffName = ClickedElement_a.textContent || ClickedElement_a.innerText;
    staffName = staffName.replace(/ответственный|назначен/g, '').trim();

    if (ClickedStaffNewCheckedValue) {
        if (staffId_element.value == '' || staffId_element.value == 0) {
            //Назначаем ответственного
            ClickedElement_a.innerHTML = staffName + ' <span class="label label-danger">ответственный</span>';
            staffId_element.value = ClickedStaffId;
        } else {
            //Назначаем исполнителя
            ClickedElement_a.innerHTML = staffName + ' <span class="label label-success">назначен</span>';
        }
    } else {
        //снимаем коржик =)
        ClickedElement_a.innerHTML = staffName;
        if (staffId == ClickedStaffId) staffId_element.removeAttribute("value");
    }
}

// Управление chevron иконками и подсветкой отделов для иерархического выбора
$(document).ready(function() {
    // Инициализация chevron при загрузке страницы
    $('.dept-staff-list').each(function() {
        var chevron = $(this).prev('.dept-header').find('.dept-chevron');
        if ($(this).hasClass('in')) {
            chevron.removeClass('fa-chevron-right').addClass('fa-chevron-down');
        }
    });

    // Обработка кликов по заголовкам отделов - переключение chevron
    $('.dept-header').on('click', function() {
        var chevron = $(this).find('.dept-chevron');
        var targetId = $(this).data('target');
        var targetPanel = $(targetId);

        // Переключаем chevron с небольшой задержкой для синхронизации с анимацией
        setTimeout(function() {
            if (targetPanel.hasClass('in')) {
                chevron.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            } else {
                chevron.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }
        }, 50);
    });

    // Подсветка отдела при выборе сотрудника
    $('.staff-checkbox input[type="checkbox"]').on('change', function() {
        var deptGroup = $(this).closest('.dept-group');
        var checkedInDept = deptGroup.find('.staff-checkbox input[type="checkbox"]:checked').length;

        if (checkedInDept > 0) {
            deptGroup.addClass('has-selected');
        } else {
            deptGroup.removeClass('has-selected');
        }
    });

    // Инициализация подсветки при загрузке страницы
    $('.dept-group').each(function() {
        var checkedInDept = $(this).find('.staff-checkbox input[type="checkbox"]:checked').length;
        if (checkedInDept > 0) {
            $(this).addClass('has-selected');
        }
    });
});