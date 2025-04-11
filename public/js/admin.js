// assets/js/admin.js

$(document).ready(function() {
    const usersTable = $('#usersTable').DataTable({
        searching: true
    });

    // Поиск по колонкам ID (0) и Username (1)
    $('#customSearch').on('keyup', function () {
        usersTable.columns([0, 1]).search(this.value).draw();
    });
    $('#agentsTable').DataTable();
    $('#tradesTable').DataTable();
});
