// assets/js/admin.js
//todo rename to search-table.js - use value for table name and realaze serach in users and agents manage tables
//old code - bad  (not used anywhere)
$(document).ready(function() {
    const table = $('#usersTable').DataTable({
        searching: true, // Включение поиска по таблице
        columnDefs: [
            { 
                targets: [0, 1], // Указываем, что поиск будет работать по первым двум столбцам (ID и Username)
                searchable: true // Включаем возможность поиска по этим столбцам
            }
        ]
    });

    // Поиск по полям (ID и Username) с использованием пользовательского ввода
    $('#customSearch').on('keyup', function() {
        table.columns([0, 1]).search(this.value).draw(); // Ищем по столбцам ID и Username
    });


    $('#agentsTable').DataTable();
    $('#tradesTable').DataTable();
});
