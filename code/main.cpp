#include "../include/pcanFunctions.h"
#include "../include/databaseFunctions.h"
#include "../include/mainFunctions.h"

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <iostream>
#include <queue>

using namespace std;

/*
 * Return the controller name based on the CAN ID.
 */
const char* getControllerName(int canId)
{
    switch (canId)
    {
        case 0x100:
            return "Server Controller";

        case 0x101:
            return "Elevator Controller";

        case 0x200:
            return "Car Controller";

        case 0x201:
            return "Floor 1 Controller";

        case 0x202:
            return "Floor 2 Controller";

        case 0x203:
            return "Floor 3 Controller";

        default:
            return "Unknown Controller";
    }
}

/*
 * Return a readable description for the CAN message.
 */
const char* getCanDescription(int canId, int data)
{
    /*
     * Elevator Controller messages.
     */
    if (canId == 0x101)
    {
        if (data == 0x04)
        {
            return "Elevator moving";
        }

        if (data == 0x05)
        {
            return "Elevator arrived at Floor 1";
        }

        if (data == 0x06)
        {
            return "Elevator arrived at Floor 2";
        }

        if (data == 0x07)
        {
            return "Elevator arrived at Floor 3";
        }
    }

    /*
     * Server Controller commands.
     */
    if (canId == 0x100)
    {
        if (data == 0x05)
        {
            return "Command sent to Floor 1";
        }

        if (data == 0x06)
        {
            return "Command sent to Floor 2";
        }

        if (data == 0x07)
        {
            return "Command sent to Floor 3";
        }
    }

    /*
     * Car Controller requests.
     */
    if (canId == 0x200)
    {
        if (data == 0x01)
        {
            return "Car requested Floor 1";
        }

        if (data == 0x02)
        {
            return "Car requested Floor 2";
        }

        if (data == 0x03)
        {
            return "Car requested Floor 3";
        }
    }

    /*
     * Floor Controller requests.
     */
    if (canId == 0x201)
    {
        if (data == 0x01)
        {
            return "Floor 1 request";
        }

        if (data == 0x02)
        {
            return "Open door request";
        }

        if (data == 0x03)
        {
            return "Close door request";
        }
    }

    if (canId == 0x202 && data == 0x01)
    {
        return "Floor 2 request";
    }

    if (canId == 0x203 && data == 0x01)
    {
        return "Floor 3 request";
    }

    return "CAN message";
}

/*
 * Return the floor connected to the CAN message.
 * Return 0 if the message is not connected to a floor.
 */
int getCanFloor(int canId, int data)
{
    /*
     * Server or Elevator Controller floor messages.
     */
    if (canId == 0x100 || canId == 0x101)
    {
        if (data == 0x05)
        {
            return 1;
        }

        if (data == 0x06)
        {
            return 2;
        }

        if (data == 0x07)
        {
            return 3;
        }
    }

    /*
     * Car Controller floor requests.
     */
    if (canId == 0x200)
    {
        if (data == 0x01)
        {
            return 1;
        }

        if (data == 0x02)
        {
            return 2;
        }

        if (data == 0x03)
        {
            return 3;
        }
    }

    /*
     * Floor Controller requests.
     */
    if (canId == 0x201 && data == 0x01)
    {
        return 1;
    }

    if (canId == 0x202 && data == 0x01)
    {
        return 2;
    }

    if (canId == 0x203 && data == 0x01)
    {
        return 3;
    }

    return 0;
}

int main()
{
    int choice;
    int ID;
    int data;
    int numRx;

    int rxID;
    int rxData;

    int floorNumber = 1;
    int prev_floorNumber = 1;

    while (1)
    {
        system("@cls||clear");

        choice = menu();

        switch (choice)
        {
            /*
             * Manual CAN transmission.
             */
            case 1:
            {
                ID = chooseID();
                data = chooseMsg();

                int sendResult = pcanTx(ID, data);

                if (sendResult == 0)
                {
                    printf("CAN message sent successfully\n");

                    int logResult = db_logCanMessage(
                        ID,
                        data,
                        "TX",
                        getControllerName(ID),
                        getCanDescription(ID, data),
                        getCanFloor(ID, data)
                    );

                    if (logResult == 0)
                    {
                        printf("TX CAN message saved to database\n");
                    }
                    else
                    {
                        printf("TX CAN database logging failed\n");
                    }

                    int selectedFloor = FloorFromHex(data);

                    if (selectedFloor >= 1 &&
                        selectedFloor <= 3)
                    {
                        db_setFloorNum(selectedFloor);
                    }
                }
                else
                {
                    printf("CAN send failed\n");
                }

                break;
            }

            /*
             * Receive a selected number of messages.
             */
            case 2:
            {
                printf("\nHow many messages to receive? ");
                scanf("%d", &numRx);

                pcanRx(numRx);

                break;
            }

            /*
             * Main website and physical CAN listener.
             */
            case 3:
            {
                printf(
                    "\nListening to website and physical CAN buttons\n"
                );

                printf("Press Ctrl+C to cancel\n");

                std::queue<int> requestQueue;

                bool elevatorBusy = false;
                int currentRequest = 0;

                db_setFloorNum(1);
                prev_floorNumber = 1;

                while (1)
                {
                    /*
                     * Check for one received CAN message.
                     */
                    int receiveResult =
                        pcanRxOne(&rxID, &rxData);

                    if (receiveResult == 1)
                    {
                        printf(
                            "Received ID: 0x%03X Data: 0x%02X\n",
                            rxID,
                            rxData
                        );

                        /*
                         * Save the received CAN message.
                         */
                        int rxLogResult = db_logCanMessage(
                            rxID,
                            rxData,
                            "RX",
                            getControllerName(rxID),
                            getCanDescription(rxID, rxData),
                            getCanFloor(rxID, rxData)
                        );

                        if (rxLogResult == 0)
                        {
                            printf(
                                "RX CAN message saved to database\n"
                            );
                        }
                        else
                        {
                            printf(
                                "RX CAN database logging failed\n"
                            );
                        }

                        /*
                         * Elevator Controller status:
                         *
                         * 0x04 = Moving
                         * 0x05 = Floor 1
                         * 0x06 = Floor 2
                         * 0x07 = Floor 3
                         */
                        if (rxID == 0x101)
                        {
                            int arrivedFloor = 0;

                            if (rxData == 0x05)
                            {
                                arrivedFloor = 1;
                            }
                            else if (rxData == 0x06)
                            {
                                arrivedFloor = 2;
                            }
                            else if (rxData == 0x07)
                            {
                                arrivedFloor = 3;
                            }

                            if (
                                elevatorBusy &&
                                arrivedFloor == currentRequest
                            )
                            {
                                printf(
                                    "Elevator arrived at Floor %d\n",
                                    arrivedFloor
                                );

                                elevatorBusy = false;
                                currentRequest = 0;
                            }
                        }

                        /*
                         * Car Controller requests.
                         */
                        else if (rxID == ID_CC_TO_SC)
                        {
                            if (rxData == 0x01)
                            {
                                requestQueue.push(1);

                                db_setFloorNum(1);
                                prev_floorNumber = 1;

                                printf(
                                    "Floor 1 added to queue\n"
                                );
                            }
                            else if (rxData == 0x02)
                            {
                                requestQueue.push(2);

                                db_setFloorNum(2);
                                prev_floorNumber = 2;

                                printf(
                                    "Floor 2 added to queue\n"
                                );
                            }
                            else if (rxData == 0x03)
                            {
                                requestQueue.push(3);

                                db_setFloorNum(3);
                                prev_floorNumber = 3;

                                printf(
                                    "Floor 3 added to queue\n"
                                );
                            }
                        }

                        /*
                         * Floor 1 Controller:
                         *
                         * 0x01 = Floor 1 request
                         * 0x02 = Open door request
                         * 0x03 = Close door request
                         */
                        else if (rxID == ID_F1_TO_SC)
                        {
                            if (rxData == 0x01)
                            {
                                requestQueue.push(1);

                                db_setFloorNum(1);
                                prev_floorNumber = 1;

                                printf(
                                    "Floor 1 added to queue\n"
                                );
                            }
                            else if (rxData == 0x02)
                            {
                                printf(
                                    "Open door request received\n"
                                );
                            }
                            else if (rxData == 0x03)
                            {
                                printf(
                                    "Close door request received\n"
                                );
                            }
                        }

                        /*
                         * Floor 2 Controller request.
                         */
                        else if (rxID == ID_F2_TO_SC)
                        {
                            if (rxData == 0x01)
                            {
                                requestQueue.push(2);

                                db_setFloorNum(2);
                                prev_floorNumber = 2;

                                printf(
                                    "Floor 2 added to queue\n"
                                );
                            }
                        }

                        /*
                         * Floor 3 Controller request.
                         */
                        else if (rxID == ID_F3_TO_SC)
                        {
                            if (rxData == 0x01)
                            {
                                requestQueue.push(3);

                                db_setFloorNum(3);
                                prev_floorNumber = 3;

                                printf(
                                    "Floor 3 added to queue\n"
                                );
                            }
                        }
                    }

                    /*
                     * Read a website floor request.
                     */
                    floorNumber = db_getFloorNum();

                    if (floorNumber != prev_floorNumber)
                    {
                        if (
                            floorNumber >= 1 &&
                            floorNumber <= 3
                        )
                        {
                            requestQueue.push(floorNumber);

                            printf(
                                "Website Floor %d added to queue\n",
                                floorNumber
                            );
                        }

                        prev_floorNumber = floorNumber;
                    }

                    /*
                     * Process the next queued request only
                     * when the elevator is available.
                     */
                    if (
                        !elevatorBusy &&
                        !requestQueue.empty()
                    )
                    {
                        int nextFloor =
                            requestQueue.front();

                        int command = 0;

                        if (nextFloor == 1)
                        {
                            command = GO_TO_FLOOR1;
                        }
                        else if (nextFloor == 2)
                        {
                            command = GO_TO_FLOOR2;
                        }
                        else if (nextFloor == 3)
                        {
                            command = GO_TO_FLOOR3;
                        }

                        int sendResult = pcanTx(
                            ID_SC_TO_EC,
                            command
                        );

                        if (sendResult == 0)
                        {
                            /*
                             * Save the successful CAN
                             * transmission in the database.
                             */
                            int txLogResult =
                                db_logCanMessage(
                                    ID_SC_TO_EC,
                                    command,
                                    "TX",
                                    getControllerName(
                                        ID_SC_TO_EC
                                    ),
                                    getCanDescription(
                                        ID_SC_TO_EC,
                                        command
                                    ),
                                    getCanFloor(
                                        ID_SC_TO_EC,
                                        command
                                    )
                                );

                            if (txLogResult == 0)
                            {
                                printf(
                                    "TX CAN message saved "
                                    "to database\n"
                                );
                            }
                            else
                            {
                                printf(
                                    "TX CAN database "
                                    "logging failed\n"
                                );
                            }

                            requestQueue.pop();

                            currentRequest = nextFloor;
                            elevatorBusy = true;

                            printf(
                                "Moving to Floor %d. "
                                "Waiting requests: %lu\n",
                                currentRequest,
                                (unsigned long)
                                    requestQueue.size()
                            );
                        }
                        else
                        {
                            printf("CAN send failed\n");
                        }
                    }

                    /*
                     * Wait 100 milliseconds before
                     * checking CAN and the database again.
                     */
                    usleep(100000);
                }

                break;
            }

            case 4:
            {
                return 0;
            }

            default:
            {
                printf("Error on input values\n");
                sleep(3);
                break;
            }
        }

        sleep(1);
    }

    return 0;
}