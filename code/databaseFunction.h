#ifndef DB_FUNCTIONS
#define DB_FUNCTIONS

int db_getFloorNum();

int db_setFloorNum(int floorNum);

int db_logCanMessage(
    int canId,
    int canData,
    const char* direction,
    const char* controllerName,
    const char* description,
    int floorNumber
);

#endif