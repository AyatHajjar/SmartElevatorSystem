int db_logCanMessage(
    int canId,
    int canData,
    const char* direction,
    const char* controllerName,
    const char* description,
    int floorNumber
)
{
    sql::Driver *driver = nullptr;
    sql::Connection *con = nullptr;
    sql::PreparedStatement *pstmt = nullptr;

    try
    {
        driver = get_driver_instance();

        con = driver->connect(
            "tcp://127.0.0.1:3306",
            "ese",
            "ese"
        );

        con->setSchema("elevator");

        pstmt = con->prepareStatement(
            "INSERT INTO CAN_logs "
            "(can_id, can_data, direction, controller_name, "
            "event_description, floor_number) "
            "VALUES (?, ?, ?, ?, ?, ?)"
        );

        pstmt->setInt(1, canId);
        pstmt->setInt(2, canData);
        pstmt->setString(3, direction);
        pstmt->setString(4, controllerName);
        pstmt->setString(5, description);
        pstmt->setInt(6, floorNumber);

        pstmt->executeUpdate();

        delete pstmt;
        delete con;

        return 0;
    }
    catch (sql::SQLException &error)
    {
        cout << "Database CAN logging error: "
             << error.what()
             << endl;

        if (pstmt != nullptr)
        {
            delete pstmt;
        }

        if (con != nullptr)
        {
            delete con;
        }

        return -1;
    }
}