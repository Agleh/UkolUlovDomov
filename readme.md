Předpokládají se v databázi "database"
tabulky user_admin a villages, vzhledem k funkčn vzájemnosti m:n, 
předpokládám vazební tabulku user_village, která bude mít 4 sloupce
 (user_id, village_id, search_right, adress_right).
 Přidání měst by se mělo obsluhovat ve třídě Villages?